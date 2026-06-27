<?php
define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_login();
global $DB, $USER, $PAGE, $CFG;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Método não permitido']));
}

try {

// Map UI slug → DB score_types name
$CRITERIA_TO_ATTR = [
    // Capacidade
    'assiduidade'        => 'Assiduidade',
    'alto_conhecimento'  => 'Alto conhecimento em um tópico',
    'boa_comunicacao'    => 'Boa comunicação',
    'comprometimento'    => 'Comprometimento',
    'cooperacao'         => 'Cooperação',
    'cumprimento_prazos' => 'Cumprimento de prazos e compromissos',
    'dedicacao'          => 'Dedicação',
    'disponibilidade'    => 'Disponibilidade',
    'eficiencia'         => 'Eficiência',
    'feedback'           => 'Feedback',
    'foco'               => 'Foco',
    'lideranca'          => 'Liderança',
    'pontualidade'       => 'Pontualidade',
    'proatividade'       => 'Proatividade',
    // Integridade
    'generosidade'       => 'Generosidade',
    'etica'              => 'Ética',
    'comportamento'      => 'Comportamento',
];

$raw_criteria = optional_param_array('criteria', [], PARAM_ALPHANUMEXT);
$prioritized_attributes = array_values(array_filter(
    array_map(fn($s) => $CRITERIA_TO_ATTR[$s] ?? null, $raw_criteria)
));

// Log the trust attributes selected by the user and forwarded to the recommender.
$debug_log = $CFG->dataroot . '/recommender_debug.log';
file_put_contents(
    $debug_log,
    "=== " . date('Y-m-d H:i:s') . " ===\n" .
    "ATRIBUTOS SELECIONADOS (slugs): " . json_encode($raw_criteria, JSON_UNESCAPED_UNICODE) . "\n" .
    "ATRIBUTOS ENVIADOS AO RECOMENDADOR: " . json_encode($prioritized_attributes, JSON_UNESCAPED_UNICODE) . "\n",
    FILE_APPEND
);

// Q-A: Trustor basic info
$trustor_row = $DB->get_record('user',
    ['id' => $USER->id],
    'id, firstname, lastname, city, country, department',
    MUST_EXIST
);

// Q-B: Candidate pool — active, non-deleted, not current user, not existing collaborators
$candidates_rows = $DB->get_records_sql(
    "SELECT DISTINCT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
            u.middlename, u.alternatename, u.city, u.country,
            u.department, u.picture, u.imagealt, u.email
     FROM {user} u
     WHERE u.id != :me
       AND u.deleted = 0
       AND u.suspended = 0
       AND u.id NOT IN (
           SELECT CASE WHEN userid = :me2 THEN collaboratorid ELSE userid END
           FROM {collaborators}
           WHERE userid = :me3 OR collaboratorid = :me4
       )",
    ['me' => $USER->id, 'me2' => $USER->id, 'me3' => $USER->id, 'me4' => $USER->id]
);

if (empty($candidates_rows)) {
    echo json_encode(['status' => 'success', 'data' => []]);
    exit;
}

$candidate_ids = array_keys($candidates_rows);
$all_ids = array_merge([$USER->id], $candidate_ids);

// Q-C: Interests for all users (batch); ti.id is unique key so all rows are preserved
[$in_sql_c, $in_params_c] = $DB->get_in_or_equal($all_ids);
$interests_rows = $DB->get_records_sql(
    "SELECT ti.id, ti.itemid AS uid, t.name
     FROM {tag} t
     JOIN {tag_instance} ti ON t.id = ti.tagid
     WHERE ti.itemid $in_sql_c",
    $in_params_c
);
$interests_by_user = [];
foreach ($interests_rows as $r) {
    $interests_by_user[$r->uid][] = $r->name;
}

// Q-D: Friends from message_contacts (batch, bidirectional); mc.id is unique key
[$in_sql_d1, $in_params_d1] = $DB->get_in_or_equal($all_ids);
[$in_sql_d2, $in_params_d2] = $DB->get_in_or_equal($all_ids);
$friends_rows = $DB->get_records_sql(
    "SELECT mc.id, mc.userid, mc.contactid
     FROM {message_contacts} mc
     WHERE mc.userid $in_sql_d1 OR mc.contactid $in_sql_d2",
    array_merge($in_params_d1, $in_params_d2)
);
$friends_by_user = [];
foreach ($friends_rows as $r) {
    $friends_by_user[$r->userid][]   = (int)$r->contactid;
    $friends_by_user[$r->contactid][] = (int)$r->userid;
}

// Q-E: Attribute averages computed from raw ratings; r.id is unique key so all rows are preserved
[$in_sql_e, $in_params_e] = $DB->get_in_or_equal($all_ids);
$attr_rows = $DB->get_records_sql(
    "SELECT r.id, e.userid, st.name AS attr_name, r.value
     FROM {local_trustymatchmaker_evals} e
     JOIN {local_trustymatchmaker_ratings} r ON r.evalid = e.id
     JOIN {local_trustymatchmaker_score_types} st ON st.id = r.scoreid
     WHERE e.userid $in_sql_e",
    $in_params_e
);
$raw_attrs = [];
foreach ($attr_rows as $r) {
    $raw_attrs[$r->userid][$r->attr_name][] = (float)$r->value;
}
$attrs_by_user = [];
foreach ($raw_attrs as $uid => $attrs) {
    foreach ($attrs as $attr_name => $values) {
        $attrs_by_user[$uid][$attr_name] = array_sum($values) / count($values);
    }
}

// Q-F: Collaboration history; r.id is unique key so all rating rows per eval are preserved
[$in_sql_f1, $in_params_f1] = $DB->get_in_or_equal($all_ids);
[$in_sql_f2, $in_params_f2] = $DB->get_in_or_equal($all_ids);
$collab_rows = $DB->get_records_sql(
    "SELECT r.id AS row_id,
            e.id AS eval_id,
            e.evaluatorid AS trustor_id,
            e.userid      AS trustee_id,
            e.timecreated,
            st.name       AS attr_name,
            r.value
     FROM {local_trustymatchmaker_evals} e
     JOIN {local_trustymatchmaker_ratings} r ON r.evalid = e.id
     JOIN {local_trustymatchmaker_score_types} st ON st.id = r.scoreid
     WHERE e.evaluatorid $in_sql_f1 OR e.userid $in_sql_f2",
    array_merge($in_params_f1, $in_params_f2)
);
$evals_by_id = [];
foreach ($collab_rows as $r) {
    $eid = $r->eval_id;
    $evals_by_id[$eid]['trustor_id']  = (int)$r->trustor_id;
    $evals_by_id[$eid]['trustee_id']  = (int)$r->trustee_id;
    $evals_by_id[$eid]['timecreated'] = $r->timecreated;
    $evals_by_id[$eid]['evaluation'][$r->attr_name] = (int)$r->value;
}
$collaboration_history = [];
foreach ($evals_by_id as $eval) {
    $avg = array_sum($eval['evaluation']) / count($eval['evaluation']);
    $collaboration_history[] = [
        'trustor_id' => $eval['trustor_id'],
        'trustee_id' => $eval['trustee_id'],
        'liked'      => ($avg >= 3.0),
        'evaluation' => $eval['evaluation'],
        'timestamp'  => date('c', (int)$eval['timecreated']),
    ];
}

// Build trustor payload
$trustor_payload = [
    'user_id'          => (int)$USER->id,
    'interests'        => $interests_by_user[$USER->id] ?? [],
    'demographic_data' => [
        'city'    => $trustor_row->city ?? '',
        'country' => $trustor_row->country ?? '',
        'age'     => 0,
        'course'  => $trustor_row->department ?? '',
    ],
    'friends'    => array_values(array_unique($friends_by_user[$USER->id] ?? [])),
    'attributes' => empty($attrs_by_user[$USER->id]) ? (object)[] : $attrs_by_user[$USER->id],
];

// Build candidates payload
$candidates_payload = [];
foreach ($candidates_rows as $c) {
    $candidates_payload[] = [
        'user_id'          => (int)$c->id,
        'interests'        => $interests_by_user[$c->id] ?? [],
        'demographic_data' => [
            'city'    => $c->city ?? '',
            'country' => $c->country ?? '',
            'age'     => 0,
            'course'  => $c->department ?? '',
        ],
        'friends'    => array_values(array_unique($friends_by_user[$c->id] ?? [])),
        'attributes' => empty($attrs_by_user[$c->id]) ? (object)[] : $attrs_by_user[$c->id],
    ];
}

$python_payload = json_encode([
    'trustor'                => $trustor_payload,
    'candidates'             => $candidates_payload,
    'collaboration_history'  => $collaboration_history,
    'prioritized_attributes' => $prioritized_attributes,
]);

// Debug log
file_put_contents($debug_log, "REQUEST:\n" . json_encode(json_decode($python_payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

// Call Python recommender microservice
$ch = curl_init('http://recommender:5000/recommend');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $python_payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$response_raw = curl_exec($ch);
$http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

file_put_contents($debug_log, "RESPONSE (HTTP $http_code):\n" . (is_string($response_raw) ? json_encode(json_decode($response_raw), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'curl error') . "\n\n", FILE_APPEND);

if ($response_raw === false || $http_code !== 200) {
    http_response_code(502);
    die(json_encode(['status' => 'error', 'message' => 'Serviço de recomendação indisponível']));
}

$py_response = json_decode($response_raw, true);
if (!$py_response || $py_response['status'] !== 'success') {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => 'Erro no motor de recomendação']));
}

// Map ranked user IDs back to Moodle profile data
$results = [];
foreach ($py_response['ranking'] as $item) {
    $uid = $item['user_id'];
    if (!isset($candidates_rows[$uid])) {
        continue;
    }
    $u = $candidates_rows[$uid];
    $userpicture = new user_picture($u);
    $userpicture->size = 1;
    $results[] = [
        'id'              => (int)$u->id,
        'fullname'        => fullname($u),
        'profileimageurl' => $userpicture->get_url($PAGE)->out(false),
        'score'           => (float)$item['score'],
    ];
}
usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

echo json_encode(['status' => 'success', 'data' => $results]);

} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()]);
}
