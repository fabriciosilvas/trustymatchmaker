from math import sqrt

def distancia_euclidiana(A, B):
    # A e B arrays de uma dimensão contendo a média dos atributos
    total = 0

    for i in range(len(A)):
        total += (A[i] - B[i]) ** 2

    return sqrt(total)