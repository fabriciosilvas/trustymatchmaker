import pandas as pd
import numpy as np

def nivel_confianca(df, trustor, trustee):
    df_reduzido = get_df_reduzido(df)

    overalls = df_reduzido[(df_reduzido['id_trustor'] == trustor) & (df_reduzido['id_trustee'] == trustee)]['liked']

    n = len(overalls)

    if not n:
        return 0

    pesos = np.arange(1, n + 1)
    pesos_normalizados = pesos / pesos.sum()

    resultado_final = overalls * pesos_normalizados

    return sum(resultado_final)

def transitividade_confianca(df, x, lista_amigos_x, lista_ids):
    dicionario = {}
    confianca = {}

    for amigo in lista_amigos_x:
        confianca_trustor_amigo = nivel_confianca(df, x, amigo)
        dicionario[amigo] = confianca_trustor_amigo

    for y in lista_ids:
        if y != x:
            confianca[y] = inferir_confianca(df, x, y, dicionario)

    return confianca


def inferir_confianca(df, trustor, trustee, lista_amigos):
    soma_trustor_amigos = 0
    soma_transitividade = 0

    for amigo, nivel in lista_amigos.items():
        confianca_trustor_amigo = nivel
        confianca_amigo_trustee = nivel_confianca(df, amigo, trustee)

        soma_trustor_amigos += confianca_trustor_amigo * confianca_amigo_trustee
        soma_transitividade += confianca_trustor_amigo

    if not soma_transitividade:
        return  0

    return soma_trustor_amigos / soma_transitividade

def get_df_reduzido(df):
    return df[['id_trustor', 'id_trustee', 'liked']]

def get_lista_amigos(df, x):
    return df[df['userid'] == x]['contactid'].tolist()
