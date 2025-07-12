import pandas as pd
import numpy as np
import re
from sklearn.metrics.pairwise import cosine_similarity
from sklearn.feature_extraction.text import TfidfVectorizer
from distancia_euclidiana import distancia_euclidiana
from remover_acentos import remover_acentos

def compara_similaridade(trustor, trustee): #trustor e trustee dataframes
    # Comparar Apresentação + Descrição com Interesses e com Medalhas

    tfidf_vec = TfidfVectorizer(stop_words='portuguese', analyzer='word', ngram_range=(1, 3))

    for df in (trustor, trustee):
        df['about'] = df['about_localizacao'].fillna('') + ' ' + df['about_curso'].fillna('') + ' ' + df['about_aniversario'].fillna('')
        df['about'] = df['about'].str.lower().str.replace(r'[^\w\s]', '', regex=True).apply(remover_acentos)
        df['description'] = df['description'].fillna('').str.lower().str.replace(r'[^\w\s]', '', regex=True).apply(remover_acentos)
        df['description'] = df['about'] + ' ' + df['description']
    desc_tor = trustor['description']
    desc_tee = trustee['description']
    tfidf_matrix = tfidf_vec.fit_transform([desc_tor, desc_tee])
    simDesc = cosine_similarity(tfidf_matrix[0], tfidf_matrix[1])[0][0]

    tags_tor = ' '.join(trustor['tags']) if isinstance(trustor['tags'], list) else ''
    tags_tee = ' '.join(trustee['tags']) if isinstance(trustee['tags'], list) else ''
    tfidf_matrix_tags = tfidf_vec.fit_transform([tags_tor, tags_tee])
    simTags = cosine_similarity(tfidf_matrix_tags[0], tfidf_matrix_tags[1])[0][0]

    medals_tor = np.array(trustor['medals'])
    medals_tee = np.array(trustee['medals'])
    simMedals = 1/(1 + distancia_euclidiana(medals_tor, medals_tee))

    return (simDesc + simTags + simMedals) / 3