import pandas as pd

def similaridade_filtragem(df, x, lista_ids, filtro_atributos):
    similaridade_ids = {}

    for i in range(len(lista_ids)):
        if lista_ids[i] != x:
            media_y = media_atributos(df, lista_ids[i], filtro_atributos)
            nivel_similaridade = sum(media_y) / len(media_y)
            similaridade_ids[lista_ids[i]] = nivel_similaridade

    return similaridade_ids

def media_atributos(df, x, lista_atributos):
    df_filtrado = df[df['id_trustee'] == x]
    media = []
    for atributo in lista_atributos:
        media_sem_zero = df_filtrado[df_filtrado[atributo] != 0][atributo].mean()
        if pd.isna(media_sem_zero):
            media_sem_zero = 0
        media.append(media_sem_zero)

    return media