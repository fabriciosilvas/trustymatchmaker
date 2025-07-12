import unicodedata

def remover_acentos(texto):
    if isinstance(texto, str):
        texto = unicodedata.normalize('NFD', texto)
        texto = texto.encode('ascii', 'ignore').decode('utf-8')
    return texto