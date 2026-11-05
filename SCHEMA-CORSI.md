# Schema reale dei corsi — analisi locandine

Fonti analizzate (cartella `foto info`):

- `Screenshot (22).png` — post Instagram @dance_club_ssd con il **programma settimanale**.
- `Screenshot (23).png` — post Instagram con locandine Tango Argentino / Bachata Popiwa e una riga di locandine “ogni mercoledì”.
- `Screenshot (24).png` — post Instagram con Bachata Popiwa, Balli di Gruppo e un video di lezione.
- `IMG-20261015-WA0000.jpg`, `IMG-20261015-WA0001.jpg`, `IMG-20261015-WA0002.jpg`
  — volantini/locandine, in gran parte doppioni o materiale serata (non corso).

## 1. Programma settimanale letto dallo screenshot

| Giorno | Ora | Corso | Sede |
|---|---|---|---|
| Lunedì | 21:00 | Latinoamericano Principianti | Hotel Lodi |
| Lunedì | 21:00 | Bachata Popiwa | La Pergola |
| Martedì | 20:30 | Ballo Country | Hotel Lodi |
| Martedì | 20:30 | Salsa Intermedio / Avanzato | La Pergola |
| Mercoledì | 20:30 | Balli di Gruppo | Hotel Lodi |
| Mercoledì | 21:00 | Salsa & Bachata Base 2 | La Pergola |
| Giovedì | 20:30 | Folklore Cubano | Hotel Lodi |
| Giovedì | 20:30 | Tango Argentino | La Pergola |

Venerdì, sabato e domenica restano le serate (non corsi):
venerdì e sabato serata caraibica, domenica pomeriggio ballo liscio.

## 2. Dettagli dalle locandine

- **Tango Argentino** — giovedì, La Pergola, maestro Felice Primo.
  La locandina indica **20:30 principianti** e **21:30 intermedio**.
- **Bachata Popiwa / Dominicana** — lunedì, La Pergola, maestro Luis Tapia Falcon
  (una locandina dice “Luis y Isabi”).
  La locandina indica **20:30 principianti** e **21:30 intermedio**.
- **Balli di Gruppo** — mercoledì, Hotel Lodi, ore 20:30, staff Dance Club.
- **Salsa / Bachata** — una locandina “ogni mercoledì” a La Pergola indica
  20:30 principianti e 21:30 intermedio; il programma settimanale indica
  invece “Salsa & Bachata Base 2” alle 21:00. Sono probabilmente due
  comunicazioni diverse della stessa serata (o due stagioni).

### Doppioni

Le locandine di Bachata Popiwa, Tango, Balli di Gruppo e Salsa/Bachata
circolano in più versioni con date di inizio diverse (15 settembre,
21 settembre, 24 settembre). Non sono corsi diversi: sono la stessa
programmazione promossa in momenti diversi. Lo schema da tenere è uno solo.

## 3. Schema dati implementato

Per avere card coerenti e un orario reale con **due corsi nella stessa sera**
non basta l’attuale `weekly_schedule` (una riga per giorno). È stata creata:

```sql
course_slots
  id, discipline_id (collegamento alla card), day_index (0 = lunedì),
  start_time, end_time, title, level, venue, teacher, note,
  is_active, sort_order
```

- Le **card** mostrano nome, descrizione, maestro e gli orari presi da `course_slots`.
- La **tabella orari** mostra tutti gli slot raggruppati per giorno.
- L’admin gestisce gli slot in **3 · I corsi → Orari dei corsi** (aggiungi/elimina).
- Le serate (ven/sab/dom) restano in `weekly_schedule`, nella sezione
  **4 · Eventi e locandine → Palinsesto serate**.

### Dati inseriti (da confermare)

Sono stati inseriti gli 8 appuntamenti del programma settimanale. Le card in
home sono state portate a 6: Salsa, Bachata, Country, Balli di Gruppo,
Folklore Cubano, Tango Argentino.

| Slot | Disciplina collegata |
|---|---|
| Latinoamericano Principianti | Salsa (Cubana e Portoricana) |
| Bachata Popiwa | Bachata (stile Popiwa) |
| Ballo Country | Country |
| Salsa Intermedio / Avanzato | Salsa (Cubana e Portoricana) |
| Balli di Gruppo | Balli di Gruppo |
| Salsa & Bachata Base 2 | Salsa (Cubana e Portoricana) |
| Folklore Cubano | Folklore Cubano |
| Tango Argentino | Tango Argentino |

Le altre discipline presenti oggi nel database (Kizomba, Ballo Liscio,
Boogie Woogie, Flamenco, Portamento Femminile e Man Style) restano nelle card
della pagina Corsi ma non sono in evidenza in home, perché non compaiono nel
programma settimanale.

## 4. Domande prima di correggere i dati

1. Il programma dello screenshot (8 corsi, due per sera) è quello **attuale**?
2. Per **Bachata Popiwa** e **Tango Argentino**: valgono gli orari del programma
   (Bachata 21:00, Tango 20:30) o quelli delle locandine (20:30 principianti +
   21:30 intermedio)?
3. **Kizomba, Ballo Liscio, Boogie Woogie, Flamenco, Portamento** sono corsi
   attivi? Se sì, servono giorno, ora, sede e maestro.
4. Confermi i maestri: Pepe Espino & Jessica per Latinoamericano/Salsa,
   Luis Tapia Falcon per Bachata e Folklore, Felice Primo per Tango,
   Staff Dance Club per Balli di Gruppo? Per **Ballo Country** chi insegna?

Le card dei corsi mostrano già giorno, ora e sede presi da `course_slots`
(es. “Lun 21:00 · Hotel Lodi”), oltre a nome, descrizione e maestro.

Ho già inserito gli 8 orari e collegato le card; appena confermi questi punti
correggo orari, livelli, maestri e discipline attive.
