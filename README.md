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
- last.fm only allows the [previous 2 weeks](http://www.last.fm/forum/34905/_/2230312) of scrobbles. all previous are ignored.
- if importing into libre.fm, this is a one-time process (there is no way to clear all history and retry)

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

### 2. Option 1: libre.fm import
This will get your listening history into libre.fm, but only the last 2 weeks will be forwarded to last.fm.

#### 2a. Convert your Listening History to last.fm export format
1. Using RdioLastConvert: 
```
php ./rdioLastConvert -i rdiohistory.json -o convertedhistory.csv
```

#### 2b. Setup libre.fm to forward scrobbles to last.fm
1. Create a libre.fm account
1. go to your libre.fm profile
2. click Edit
3. click `Connections to other services`
4. click `Connect to a last.fm account`
5. login to last.fm
6. click `Yes, Allow access`
7. `forward scrobbles?` should already be set to `yes`

#### 2c. Import Listening History to libre.fm
1. using libreimport.py from [lastscrape](https://github.com/encukou/lastscrape-gui):
```
libreimport.py <my username> convertedhistory.csv
```

### 3. Option 2: use universal scrobbler to import to last.fm
this method will get your history into last.fm, with the following detractions:
- you'll lose your timestamps
- you'll have to pay for premium universal scrobbler for bulk upload
- you'll have to do it in batches of ~6000 (estimated at 3min tracks)
- all your tracks will be overlapping the last 2 weeks

#### 3a. Convert your Listening History to last.fm export format
1. Using RdioLastConvert: 
```
php ./rdioLastConvert -i rdiohistory.json -o convertedhistory.csv -u
```

#### 3b. Import with universal scrobbler
1. go to [Universal Scrobbler](http://universalscrobbler.com/)
2. click Login to last.fm, and allow access to universal scrobbler
3. pay for premium Universal Scrobbler
4. cut and past a max of 6000 tracks at a time into the bulk import
5. click Scrobble, and wait for feedback

Your Rdio Listening History should now exist in last.fm (albeit inaccurately) and/or libre.fm.
