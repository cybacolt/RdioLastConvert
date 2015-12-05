<?php
/** Converts Rdio JSON blob to last.fm export format
 *
 * artist MBID lookups are made to the MusicBrainz API and cached
 *
 * MusicBrainz Licence: http://musicbrainz.org/doc/Live_Data_Feed
 * API: http://musicbrainz.org/doc/Development/XML_Web_Service/Version_2
 *
 * PHP version 5.5
 *
 * @category Rdio
 * @package  RdioLastConverter
 * @author   Rowcliffe Browne <rbkbrowne@gmail.com>
 * @license  http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link     https://github.com/cybacolt
*/
namespace Rdio;

$config = array();

$options = getopt('i:o:h');

if(empty($options)) {
    $options['h'] = 'h';
}

foreach ($options as $option => $optionValue) {
    switch ($option) {
        case "h":
            print "php rdioLastConvert.php -i <file> -o <file> -h\n";
            print "\n";
            print "\t -i\tinput file\n";
            print "\t -o\toutput file\n";
            print "\t -h\tdisplay help\n";
            print "\n";
            die();
            break; // gotta love PSR2 standards...
        case "i":
            $config['input'] = $optionValue;
            break;
        case "o":
            $config['output'] = $optionValue;
            break;
        default:
            break;
    }
}

try {

    if (array_key_exists('input', $config) && array_key_exists('output', $config)) {

    $rdioConvert = new RdioLastConverter($config['input']);
    $output = $rdioConvert->lastConvert();

    file_put_contents('./'.$config['output'], $output);
    } else {
        throw new \Exception("missing arguments...\n");
    }
} catch (\Exception $e) {
    print $e->getMessage();
}

/** RdioLastConverter
 * 
 * Converts Rdio JSON blob to last.fm export format
 *
 * @category Rdio
 * @package  RdioLastConverter
 * @author   Rowcliffe Browne <rbkbrowne@gmail.com>
 * @license  http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link     https://github.com/cybacolt
*/
class RdioLastConverter
{
    private $artistLookup = array();
    private $rdioHistory = '';
    private $fauxLastFMHistory = array();
    private $options = array(
                            'http' =>
                            array(
                                'method' => "GET",
                                'header' => "User-Agent: Rdio History Lookup/1.0 ( rbkbrowne@gmail.com )\r\n"));
    private $throttled = array();

    private function getArtistMBID($artist)
    {
        if (array_key_exists($artist, $this->artistLookup)) {
            return $this->artistLookup[$artist];
        }
        return false;
    }
    private function lookupArtistMBID($artist)
    {
        $context = stream_context_create($this->options);
        $url = "http://musicbrainz.org/ws/2/artist/?query=".urlencode(preg_quote($artist, '/'))."&fmt=json&limit=1";
        for ($i=1; $i<=3; $i++) {
            print "Looking Up ".$artist."... ";
            sleep(1); // avoid MB throttling
            $lookup = json_decode(file_get_contents($url, false, $context));

            if ($lookup) {
                break;
            }
        }

        if (!$lookup) {
            $this->throttled[] = $artist;
            if (count($this->throttled) > 9) {
                // remove failures from cache and die
                foreach ($this->throttled as $item) {
                    unset($this->artistLookup[$item]);
                }
                file_put_contents('./lookup.cache', json_encode($this->artistLookup));
                throw new \Exception("possibly throttled -- stopped after 10 failed attempts.\n");
            }
            $this->artistLookup[$artist] = '';

        } elseif ($lookup->count < 1 || levenshtein(strtolower($artist), strtolower($lookup->artists[0]->name)) > 2) {
            // if not found or almost exactly the same, do not match with MBID
            $this->artistLookup[$artist] = '';
            print "not found.\n";
            return false;
        } else {
            $this->artistLookup[$artist] = $lookup->artists[0]->id;
            print $this->artistLookup[$artist]."\n";
        }
        file_put_contents('./lookup.cache', json_encode($this->artistLookup));

        return $this->artistLookup[$artist];
    }

    public function __construct($fileName)
    {
        $this->rdioHistory = json_decode(file_get_contents('./'.$fileName));
        if (file_exists('./lookup.cache')) {
            // reduce MB lookups as much as possible if restarted
            $this->artistLookup = json_decode(file_get_contents('./lookup.cache'), true);
        }
    }

    public function lastConvert ()
    {
        $i = 0;
        foreach ($this->rdioHistory->result->sources as $sources) {
            foreach ($sources->tracks->items as $track) {
                $scrobbleTime = $track->time;
                $track = $track->track;
                $buffer = array();
                // unixtimestamp    trackname   artist     album    trackMBID  (optional)      artistMBID (optional)    albumMBID (optional)
                $buffer['time'] = strtotime($scrobbleTime);
                $buffer['name'] = $track->name;
                $buffer['artist'] = $track->artist;
                $buffer['album'] = $track->album;
                $buffer['trackMBID'] = ''; // too ambigious to find easily, so skipping
                $buffer['artistMBID'] = $this->getArtistMBID($track->artist);
                $buffer['albumMBID'] = ''; // too ambigious to find easily, so skipping
                if ($buffer['artistMBID'] === false) {
                    $buffer['artistMBID'] = $this->lookupArtistMBID($track->artist);
                }

                $this->fauxLastFMHistory[] = implode("\t", $buffer)."\n";
                $i++;
                if ($i % 10 === 0) {
                    print $i." tracks\n";
                }
            }
        }

        print count($this->fauxLastFMHistory)." complete!\n";
        return $this->fauxLastFMHistory;
    }
}
