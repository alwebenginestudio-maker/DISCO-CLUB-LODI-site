# Stile del sito — Dance Club Lodi

Sistema grafico del sito pubblico. Serve per mantenere coerenti le pagine e
per non reintrodurre gli elementi che erano stati tolti nella semplificazione.

## Nome e marchi

- Il nome principale del sito è **Dance Club Lodi**: header, footer, titoli
  delle pagine e pannello usano questo nome.
- **Latin Club** resta come etichetta dei corsi seguiti direttamente da Pepe
  Espino: sulle schede corso compare il badge “LatinClub” in basso a destra.
- La ragione sociale resta **Dance Club Società Sportiva Dilettantistica a r.l.**
  (privacy, termini e dati societari).

## Direzione

Club contemporaneo, elegante, con il lilla come colore del cliente. Nero
prugna, lilla e bianco freddo: niente oro (è il colore di AL WebEngine, non
del cliente). Tipografia moderna, fotografia protagonista, sezioni numerate e
molto respiro.

## Palette

| Ruolo | Valore | Uso |
|---|---|---|
| Prugna | `#1C1626` | header, hero, sezioni scure, footer |
| Prugna 2 | `#251D31` | pannelli sopra il nero |
| Lilla chiaro | `#FAF7FE` | sfondo pagina, testo su fondo scuro |
| Lilla carta | `#F1EBFA` | sezioni alternate |
| Lilla primario | `#6D4AA8` | bottoni, link, form, elementi interattivi |
| Lilla primario scuro | `#553780` | hover |
| Lilla accento | `#7E5BB3` | etichette, numeri, fili decorativi |
| Lilla soft | `#C9B6E8` | fasce CTA e appuntamento, accenti su fondo scuro |
| Testo | `#241E2E` | testi su fondo chiaro |
| Testo attenuato | `#6C6378` | paragrafi su lilla chiaro |
| Testo attenuato (scuro) | `#B8ADC9` | paragrafi su prugna |
| Linea | `#E2D9EE` | bordi su lilla chiaro |

I colori sono modificabili dal pannello admin (`1 · Il sito → Colori del tema`).
I valori qui sopra sono i default del tema.

## Tipografia

- **Titoli:** `Bricolage Grotesque` (400/500/600/700), peso 500, tracking negativo.
- **Testo e interfaccia:** `Inter` (400/500/600).
- **Corsivo decorativo:** `Instrument Serif` italic, usato solo per una parola
  o una riga (es. "dal 2001", "una pista", "Vieni a ballare").
- **Etichette:** Inter maiuscolo, 9–10,5 px, spaziatura `.16em`, lilla.

Non usare altre famiglie. Il corsivo non va usato per interi paragrafi.

## Componenti

- **Header:** prugna, bordo lilla sottile, logo tipografico con il nome del
  sito (“Dance Club Lodi”) + “Scuola di ballo · dal 2001”. Voce attiva con
  sottolineatura lilla, CTA lilla.
- **Hero:** foto a tutto schermo con velo prugna, titolo grande, una parte del
  titolo in corsivo lilla, due pulsanti, riga informazioni in basso.
- **Fascia appuntamento:** countdown fisso in lilla soft: giorno/ora/titolo
  configurabili da admin, nessuna data da aggiornare. Si può nascondere.
- **Intro / Il club:** testo + due foto sovrapposte (cena e venue), numeri
  sintetici (anno, discipline, sedi).
- **Corsi:** card bianche con filo lilla in alto, categoria lilla, numero lilla.
- **Serate:** sezione prugna, foto verticale a sinistra e palinsesto a destra,
  locandine sotto.
- **Tessera:** card membership (senza QR) + tre passaggi numerati; il pulsante
  apre la domanda online 4gest.
- **CTA:** fascia lilla soft con titolo in prugna e corsivo lilla scuro.
- **Social:** due profili Instagram distinti, “Serate” e “Corsi”, mostrati
  separatamente in footer e nella pagina Contatti.
- **Footer:** parte personalizzata a tre colonne + blocco finale standard
  AL WebEngine (copyright, logo `footer-logo.png`, link Admin), con bordo
  superiore nel colore primario.

## Footer standard

Il blocco finale del footer è quello degli starter storici (Lavanderia Nord
Servizi, Vidardo Basket) e non va cambiato:

```html
<div class="footer-brandbar">
    <p>© ...</p>
    <div class="footer-logo-container">
        <a href="https://www.alwebengine.it" target="_blank" rel="noopener"
           aria-label="AL WebEngine — visita alwebengine.it"
           title="Powered by AL WebEngine">
            <img src="assets/img/footer-logo.png" alt="AL WebEngine" class="footer-logo-img">
        </a>
    </div>
    <p class="footer-admin">
        <a href="admin.php" title="Area di amministrazione">
            <i class="fas fa-lock"></i> Admin
        </a>
    </p>
</div>
```

## Countdown serata

Il countdown in home è fisso e settimanale, senza data:

- admin: `4 · Eventi e locandine → Countdown della prossima serata`
- campi: giorno (lun–dom), ora, titolo, mostra/nascondi
- default: venerdì, 20:30, “Serata Caraibiche”
- durante la serata (dalle 20:30 alle 01:30) mostra “Stasera si balla”
- il codice è in `assets/js/countdown.js`

## Tessera online

La tessera non usa più un QR interno. Il pulsante apre il link esterno
configurato in admin (`1 · Il sito → Tessera associativa`), di default:

`https://4gest.4settori.net/it/domanda/danceclub.html`

## Instagram

Due profili distinti, configurabili da admin:

- **Serate:** `https://www.instagram.com/pergo_latino_danceclub`
- **Corsi:** `https://www.instagram.com/dance_club_ssd`

Sono mostrati separatamente nel footer e nella pagina Contatti, oltre che
nei pulsanti delle pagine Corsi e Serate.

## Corsi

L’analisi delle locandine e lo schema dei corsi sono in `SCHEMA-CORSI.md`.
Il palinsesto reale usa la tabella `course_slots` (più corsi nello stesso
giorno) ed è gestito da **3 · I corsi → Orari dei corsi**.

- Pagina Corsi: card delle discipline + tabella “Quando si balla” con tutti
  gli appuntamenti della settimana.
- Home: 6 card in evidenza (Salsa, Bachata, Country, Balli di Gruppo,
  Folklore Cubano, Tango Argentino), ognuna con i suoi orari.
- Soci: tabella unica della settimana con corsi (lun–gio) e serate (ven–dom).
- Le serate restano in `weekly_schedule`, gestite da
  **4 · Eventi e locandine → Palinsesto serate**.

Gli orari inseriti sono quelli del programma settimanale dello screenshot;
le domande su Bachata/Tango e sulle discipline non attive sono in
`SCHEMA-CORSI.md`.

### Etichetta “LatinClub”

I corsi con maestro **Pepe Espino** (campo `teacher` della disciplina)
mostrano il badge “LatinClub” in basso a destra nella scheda corso, sia in
home sia nella pagina Corsi. Il controllo è nella funzione
`isLatinClubCourse()` in `functions.php`, lo stile in `.course-card__brand`:
basta cambiare il maestro della disciplina per far comparire o sparire
l’etichetta.

## Pagina Storia

La storia del club ha una pagina dedicata, `storia.php`:

- hero con titolo configurabile (`chi_siamo_title`, default “La nostra storia”);
- testo della storia (`chi_siamo_text`) e numeri del club;
- corpo docente (`staff_title`, `staff_intro`, `staff_list`);
- sedi e dati societari;
- CTA verso corsi e tessera.

I testi si modificano da **2 · I testi → Storia & corpo docente**.
Il vecchio `chi-siamo.php` reindirizza a `storia.php` (301). La pagina Soci
mantiene solo un riepilogo con il link alla storia.

## Cosa è stato tolto

- Countdown legato a una data/evento e card "prossima apertura" sovrapposta alla hero.
- QR della tessera: sostituito dal link diretto alla domanda online 4gest.
- Filtri e ricerca dei corsi (le discipline sono poche e si leggono tutte).
- Lightbox delle immagini (le locandine si aprono in una scheda).
- Barra fissa di azioni rapide su mobile.
- Gradienti radiali, bagliori, ombre colorate, testi in gradiente.
- Card corsi scure alternate a card chiare.
- Logo immagine "latin club EVENTS" nell'header: sostituito dal logotipo
  tipografico. Dal 5/11/2026, se nel pannello (Impostazioni → Logo) è
  caricato un logo, compare **a sinistra accanto al testo** (classe
  `brand__logo`, 46px di altezza, 38px su mobile); senza logo resta solo il
  logotipo tipografico.
- Favicon: dal 5/11/2026 vale il **logo caricato dal pannello**
  (`renderFavicon()` controlla prima `site_logo`); se non c'è, resta il
  quadrato nero/lilla di default (`assets/img/favicon.png`). Per questo le
  pagine leggono `site_logo` con default vuoto: il vecchio default
  (`latinclub-wordmark.png`) farebbe vincere il wordmark sulla favicon.
- `assets/js/main.js` (countdown, filtri, lightbox): rimosso.

## Note su AL WebEngine

Come da `agents.md` nella cartella di progetto:

- il blocco "powered by" nel footer è quello standard, con
  `assets/img/footer-logo.png` e link a `alwebengine.it`;
- il pannello admin mantiene la stessa schermata di accesso e lo stesso stile
  degli altri pannelli AL WebEngine;
- nei moduli di contatto non sono state aggiunte funzioni di invio email
  (`mail()`/SMTP): le richieste vengono salvate nel database;
- `install.php` si blocca se il database esiste già, per non sovrascrivere
  impostazioni e password.

## Presentazione al cliente

Fuori dal sito, in `../presentazione.html`, c'è la scaletta di presentazione:
un documento HTML autonomo, senza immagini esterne, pensato come traccia da
seguire durante l'incontro con il cliente (cosa dire, cosa mostrare, cosa
cliccare, domande probabili). Non fa parte del sito pubblicato.

## File principali

- `assets/css/style.css` — tutto il sistema grafico pubblico.
- `header.php`, `footer.php`, `cookie-banner.php` — parti comuni.
- `index.php`, `storia.php`, `corsi.php`, `serate.php`, `soci.php`,
  `contatti.php`, `privacy.php`, `termini.php` — pagine pubbliche.
- `locandine-block.php` — blocco locandine condiviso da home e serate.
