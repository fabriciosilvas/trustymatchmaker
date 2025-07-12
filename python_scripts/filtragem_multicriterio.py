import pandas as pd
from distancia_euclidiana import distancia_euclidiana
from transitividade_confianca import nivel_confianca

def calculo_z(lista):
    soma = 0

    for i in range(len(lista)):
        soma += lista[i][1]

    if not soma:
        return 0

    return 1 / soma

def media_avaliacoes_x_rel_y(df, x, y, lista_atributos):
    media_x_y = []

    resultado_x = df[(df['trustor'] == x) & (df['trustee'] == y)]

    for atr in lista_atributos:
        media = resultado_x[resultado_x[atr] != 0][atr].mean()
        if pd.isna(media):
            media = 0
        media_x_y.append(media)

    return media_x_y


def distancia_x_y(df, x, y, lista_atributos):
    filtragem = df[df['trustor'].isin([x, y])]

    # Encontrar trustees que aparecem com ambos os trustores
    trustee_counts = filtragem.groupby('trustee')['trustor'].nunique()

    valid_trustees = trustee_counts[trustee_counts == 2].index

    if not len(valid_trustees):
        return 99999

    total = 0

    for trustee in valid_trustees:
        media_x_w = media_avaliacoes_x_rel_y(df, x, trustee, lista_atributos)
        media_y_w = media_avaliacoes_x_rel_y(df, y, trustee, lista_atributos)
        mxw_temp = []
        myw_temp = []

        for i in range(len(media_x_w)):
            if media_x_w[i] and media_y_w[i]:
                mxw_temp.append(media_x_w[i])
                myw_temp.append(media_y_w[i])

            total += distancia_euclidiana(mxw_temp, myw_temp)

    return total / len(valid_trustees)


def calculo_similaridade_x_y(df, x, y, lista_atributos):
    distancia = distancia_x_y(df, x, y, lista_atributos)

    return 1 / (1 + distancia)


def filtragem_colaborativa(df, ids, x, lista_atributos):
    lista = []

    for ID in ids:
        if ID != x:
            lista.append((ID, calculo_similaridade_x_y(df, x, ID, lista_atributos)))

    lista.sort(key=lambda x: x[1], reverse=True)

    qtd = len(lista) // 2

    novalista = lista[:qtd]

    z = calculo_z(novalista)

    predicao = []

    for elemento in ids:
        if elemento[0] != x:
            valor = 0
            for ID, similaridade in novalista:
                if ID != elemento:
                    valor += similaridade * nivel_confianca(df, ID, elemento)
            valor *= z

            predicao.append((elemento, valor))

    return predicao