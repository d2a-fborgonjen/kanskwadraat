<?php

namespace Coachview\Sync\Models;

use DateTime;
use Illuminate\Support\Collection;

/*

(
    [id] => 47a8c98a-9ad5-4bc5-909c-3eff9485d32c
    [code] => PC-KLAS-77-Utrecht
    [naam] => Opleiding De pedagogisch coach
    [opleidingStatusId] => TeStarten
    [opmerking] =>
    [publicatie] => 1
    [publicatiePlanning] => 1
    [planningstype] => GeenRoostering
    [planningsfrequentieAantal] => 1
    [planningsfrequentieTijdseenheid] => Dag
    [planningWeekdagen] => geen
    [planningConflictafhandeling] => GebruikEerstBeschikbareDag
    [begindatumOorspronkelijkeOpleiding] =>
    [redenOpleidingGeannuleerdOmschrijving] =>
    [deadlineInschrijven] => 1
    [deadlineUitschrijven] => 1
    [opleidingssoortId] => eee7e3ad-533a-4554-a0fc-d2923fe20ccf
    [contactpersoonId] => 3d74e4bc-baef-4c26-9591-d33f7d4cc297
    [contactpersoon2Id] =>
    [startLocatieId] => f2cd4ebb-11a0-4619-b44b-4ec6f5c52dce
    [redenOpleidingGeannuleerdId] =>
    [startDag] => Vrijdag
    [eindDag] => Vrijdag
    [startDatum] => 2025-06-06T09:30:00
    [eindDatum] => 2025-12-12T17:00:00
    [aantalPlaatsenMin] => 5
    [aantalPlaatsenMax] => 12
    [aantalPlaatsenVrij] => 12
    [aantalPlaatsenBezet] => 0
    [totaalStudiebelasting] => 0
    [totaalAantalUur] => 52.5
    [aantalOnderdelen] => 7
    [aantalPersonenOpIngediendeWebaanvragen] =>
    [auditTrail] => Array
        (
            [aangemaaktDatumTijd] => 2025-01-16T15:47:18.433
            [aangemaaktDoor] => lindsey.derks
            [gewijzigdDatumTijd] => 2025-01-22T11:34:10.919
            [gewijzigdDoor] => lindsey.derks
            [etag] => 240923003
        )

)
 */

class Training {
    public function __construct(
        public string   $id,
        public string   $code,
        public string   $name,
        public string   $status,                // e.g. 'TeStarten', 'Definitief'
        public string   $start_day,             // e.g. 'Vrijdag'
        public string   $start_date,            // e.g. '2025-06-06T09:30:00'
        public string   $end_date,              // e.g. '2025-12-12T17:00:00'
        public float    $total_study_hours,     // e.g. 52.5
        public int      $total_days,            // e.g. 5
        public int      $num_seats_taken,       // e.g. 0
        public int      $num_seats_available,   // e.g. 12
        public int      $min_seats,             // e.g. 5
        public int      $max_seats,             // e.g. 12
        public array    $locations,
        public Collection $components
    ) {
    }

    public static function from_array(array $data, Collection $components): self {
//        error_log("Training::from_array called with data: ");
        error_log(print_r($data, true));

        $locations = $components->pluck('location')->unique()->toArray();
        $total_days = $components->pluck('date')->unique()->count();
        return new self(
            id: $data['id'],
            code: $data['code'],
            name: $data['naam'],
            status: $data['opleidingStatusId'],
            start_day: $data['startDag'],
            start_date: $data['startDatum'], // DateTime::createFromFormat("Y-m-d\TH:i:s", $data['startDatum']),
            end_date: $data['eindDatum'], // DateTime::createFromFormat("Y-m-d\TH:i:s", $data['eindDatum']),
            total_study_hours: $data['totaalStudiebelasting'] ?? 0.0,
            total_days: $total_days ?? 0,
            num_seats_taken: $data['aantalPlaatsenBezet'] ?? 0,
            num_seats_available: $data['aantalPlaatsenVrij'] ?? 0,
            min_seats: $data['aantalPlaatsenMin'] ?? 0,
            max_seats: $data['aantalPlaatsenMax'] ?? 0,
            locations: $locations,
            components: $components
        );
    }
}