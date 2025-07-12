import pandas as pd
from distancia_euclidiana import distancia_euclidiana

def similaridade(df, x, lista_ids, lista_atributos):
    similaridade_ids = {}
    media_x = media_atributos(df, x, lista_atributos)

    for i in range(len(lista_ids)):
        if lista_ids[i] != x:
            media_y = media_atributos(df, lista_ids[i], lista_atributos)
            nivel_similaridade = similaridade_x_y(media_x, media_y)
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

def similaridade_x_y(x, y):
    # A e B arrays de uma dimensão contendo a média dos atributos

    data = {'a': [], 'b': []}

    for i in range(len(x)):
        if x[i] and y[i]:
            data['a'].append(x[i])
            data['b'].append(y[i])

    df = pd.DataFrame(data)

    if df['a'].std() != 0 and df['b'].std() != 0:
        df.corr(method='pearson')
        sim = df['a'].corr(df['b'])
        if sim < 0:
            sim = 0
    else:
        sim = 1 / (1 + distancia_euclidiana(data['a'], data['b']))

    return sim
