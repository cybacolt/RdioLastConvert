# RdioLastConvert
Converts Rdio Listening History JSON Blob to last.fm exported format. Allows Rdio History to be forwarded to last.fm via libre.fm

## How to import your Rdio Listening History to last.fm

## Requirements
- Chrome
- Linux / cURL / PHP 5.x / Python 2.7
- [rdioLastConvert.php](https://github.com/cybacolt/RdioLastConvert)
- libreimport.py from the (defunct) [LastToLibre](https://gitorious.org/fmthings/lasttolibre) project, available as part of this [lastscrape](https://github.com/encukou/lastscrape-gui)

## Limitations
- Rdio only stores 1 year of history
- Rdio artist names dont always map to Music Brainz artist names
- RdioLastConvert *does not do* track MBID and album MBID lookups (too ambiguous to do accurate)
- there may be limitations on *how many* scrobbles libre.fm can send to last.fm
- using 1 libre.fm account, this is a one-time process (there is no way to clear all history and retry)

### 1. Get your Listening History from Rdio
1. Log into Rdio using Chrome
2. Go to your Listening History
3. Open up the developers console (F12), and select the network tab
4. Press PGDN a few times in your listening history
5. in the network tab, locate and select the name `getHistoryForUser`
6. Right click on it and select `Copy as cURL`
7. Paste the output into a text editor
8. change the start and count values (near the end) to `start=0&count=99999`
9. at the end, add  `>> rdiohistory.json`

### 2. Convert your Listening History to last.fm export format
1. Using RdioLastConvert: 
```
php ./rdioLastConvert -i rdiohistory.json -o convertedhistory.csv
```

### 3. Setup libre.fm to forward scrobbles to last.fm
1. Create a libre.fm account
1. go to your libre.fm profile
2. click Edit
3. click `Connections to other services`
4. click `Connect to a last.fm account`
5. login to last.fm
6. click `Yes, Allow access`
7. `forward scrobbles?` should already be set to `yes`

### 4. Import Listening History to libre.fm
1. using libreimport.py from [lastscrape](https://github.com/encukou/lastscrape-gui):
```
libreimport.py <my username> convertedhistory.csv
```

Your Rdio Listening History should now safely exist in last.fm!
