<?php

namespace Amdad121\CurrencyExchange\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class CurrencyExchangeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __invoke(Request $request)
    {
        // Get the data from the API
        $response = Http::get('https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');

        // Retrieve the data
        $data = json_decode(json_encode(simplexml_load_string($response->body())), true)['Cube']['Cube']['Cube'];

        $rate = 1;
        foreach ($data as $item) {
            foreach ($item as $newitem) {
                if ($newitem['currency'] == strtoupper($request->input('currency'))) {
                    $rate = $newitem['rate'] * $request->input('amount');
                }
            }
        }

        // Create a new SimpleXMLElement object
        $xml = new SimpleXMLElement('<gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01"/>');

        $xml->addAttribute('xmlns', 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref');

        // Add child elements to the gesmes:Envelope
        $xml->addChild('gesmes:subject', 'Reference rates');

        // Add a Sender child element to the gesmes:Envelope
        $sender = $xml->addChild('gesmes:Sender');
        $sender->addChild('gesmes:name', 'European Central Bank');

        // Add more child elements to the Cube
        $cube = $xml->addChild('Cube');
        $cube = $cube->addChild('Cube');
        $cube->addAttribute('time', today()->format('Y-m-d'));
        $cube->addChild('Cube', $rate)->addAttribute('currency', strtoupper($request->input('currency')));

        // Return the XML response
        return response($xml->asXML())
            ->header('Content-Type', 'text/xml');
    }
}
