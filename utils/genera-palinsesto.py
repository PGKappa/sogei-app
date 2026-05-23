#!/usr/bin/env python3
"""
Genera un CSV palinsesto per un canale virtuale.

Uso:
    python genera-palinsesto.py <channel_id> <intervallo_minuti> [inizio_minuti] [fine_minuti]

Argomenti:
    channel_id        ID del canale (es. 101)
    intervallo_minuti Ogni quanti minuti si ripete l'evento (es. 4)
    inizio_minuti     Minuto di partenza del palinsesto (default: 0 = mezzanotte)
    fine_minuti       Minuto di fine del palinsesto (default: inizio + 1440 = 24h)

Colonne CSV:
    channel_id  ID canale
    orario      Minuti dall'inizio del giorno (0-1439)
    durata      Minuti alla prossima gara (= intervallo)
    posizione   P=primo evento, I=intermedio, U=ultimo evento
    giorno      0=giorno corrente, 1=giorno successivo (se orario supera mezzanotte)
"""

import sys
import csv


def main():
    if len(sys.argv) < 3:
        print(__doc__)
        sys.exit(1)

    channel_id = sys.argv[1]
    interval = int(sys.argv[2])
    start = int(sys.argv[3]) if len(sys.argv) > 3 else 0
    end = int(sys.argv[4]) if len(sys.argv) > 4 else start + 1440

    if interval <= 0:
        print("Errore: intervallo_minuti deve essere > 0")
        sys.exit(1)
    # se fine < inizio significa che finisce il giorno dopo (es. 360→60 = 06:00→01:00+1)
    if end <= start:
        end += 1440

    # Genera tutti gli orari dall'inizio alla fine
    events = list(range(start, end, interval))
    n = len(events)

    output_file = f"palinsesto_{channel_id}.csv"

    with open(output_file, "w", newline="") as f:
        writer = csv.writer(f)
        writer.writerow(["channel_id", "orario", "durata", "posizione", "giorno"])

        for i, t in enumerate(events):
            if i == 0:
                posizione = "P"
            elif i == n - 1:
                posizione = "U"
            else:
                posizione = "I"

            # giorno=1 solo sulla riga in cui cambia il giorno, poi torna a 0
            prev_day = events[i - 1] // 1440 if i > 0 else t // 1440
            giorno = 1 if (t // 1440) > prev_day else 0
            mins = t % 1440             # minuti dall'inizio del giorno corrente
            hh = mins // 60
            mm = mins % 60
            orario = f"{hh:02d}:{mm:02d}:00.00000"

            writer.writerow([channel_id, orario, interval * 60, posizione, giorno])

    print(f"Generato '{output_file}' con {n} eventi (intervallo {interval} min, {start}-{end} min)")


if __name__ == "__main__":
    main()
