<?php

namespace Coachview\Models;

use function Coachview\Sync\firstNonEmpty;

/*
[0] => Array(
    [id] => 83f9c36b-b28e-4ecc-8e88-45dd8d05244a
    [code] => PC-KLAS/7
    [naam] => E-learning
    [omschrijving] =>
    [datum] =>
    [tijdVan] => 09:30:00
    [tijdTot] => 17:00:00
    [volgnummer] => 7
    [studiebelasting] => 0
    [minCursisten] => 5
    [maxCursisten] => 12
    [aanwezig] => 1
    [examen] =>
    [elearningOmgevingId] => 6
    [lesvormId] => Elearning
    [elearningCode] => 21
    [planningDagenNaVorigOnderdeel] => 0
    [aantalVrij] => 12
    [virtualClassroomURL] =>
    [opleidingId] => 47a8c98a-9ad5-4bc5-909c-3eff9485d32c
    [opleiding] => Array
        (
            [id] => 47a8c98a-9ad5-4bc5-909c-3eff9485d32c
            [code] => PC-KLAS-77-Utrecht
            [naam] => Opleiding De pedagogisch coach
            [opleidingStatusId] => TeStarten
            [opmerking] =>
            [publicatie] => 1
            [publicatiePlanning] => 1
            [planningstype] => GeenRoostering
            [planningsfrequentieAantal] =>
            [planningsfrequentieTijdseenheid] =>
            [planningWeekdagen] => geen
            [planningConflictafhandeling] => GebruikEerstBeschikbareDag
            [begindatumOorspronkelijkeOpleiding] =>
            [redenOpleidingGeannuleerdOmschrijving] =>
            [deadlineInschrijven] => 1
            [deadlineUitschrijven] => 1
            [opleidingssoortId] => eee7e3ad-533a-4554-a0fc-d2923fe20ccf
            [contactpersoonId] => 3d74e4bc-baef-4c26-9591-d33f7d4cc297
            [contactpersoon2Id] =>
            [redenOpleidingGeannuleerdId] =>
            [auditTrail] => Array
                (
                    [aangemaaktDatumTijd] => 2025-01-16T15:47:18.433
                    [aangemaaktDoor] => lindsey.derks
                    [gewijzigdDatumTijd] => 2025-01-22T11:34:10.919
                    [gewijzigdDoor] => lindsey.derks
                    [etag] => 240923003
                )

        )

    [opleidingssoortonderdeelId] => 84d4f0b7-bb13-44c3-bb3f-9f5f5bbb5397
    [elearningKoppelingCustomerId] => 55fe4593-687c-44a5-b68b-8ebbe31a2884
    [elearningKoppelingCustomer] => Array
        (
            [id] => 55fe4593-687c-44a5-b68b-8ebbe31a2884
            [naam] => Default tenant
            [auditTrail] => Array
                (
                    [aangemaaktDatumTijd] => 2022-05-25T10:44:20.57
                    [aangemaaktDoor] => johan.steltman
                    [gewijzigdDatumTijd] => 2022-12-27T09:40:45.151
                    [gewijzigdDoor] => johan.steltman
                    [etag] => 1789469786
                )
        )
    [locatieId] =>
    [locatie] =>
)
[1] => Array
(
    [id] => a19908d8-81a7-4869-bdc2-12f1e4500e1e
    [code] => PC-KLAS/1
    [naam] => De kern van coachen
    [omschrijving] =>
    [datum] => 2025-06-06
    [tijdVan] => 09:30:00
    [tijdTot] => 17:00:00
    [volgnummer] => 1
    [studiebelasting] => 0
    [minCursisten] => 5
    [maxCursisten] => 12
    [aanwezig] => 1
    [examen] =>
    [elearningOmgevingId] =>
    [lesvormId] => Klassikaal
    [elearningCode] =>
    [planningDagenNaVorigOnderdeel] => 0
    [aantalVrij] => 12
    [virtualClassroomURL] =>
    [opleidingId] => 47a8c98a-9ad5-4bc5-909c-3eff9485d32c
    [opleidingssoortonderdeelId] => 754f301d-6584-4819-86a0-6ca5989fe0a1
    [elearningKoppelingCustomerId] =>
    [elearningKoppelingCustomer] =>
    [locatieId] => f2cd4ebb-11a0-4619-b44b-4ec6f5c52dce
    [locatie] => Array
        (
            [id] => f2cd4ebb-11a0-4619-b44b-4ec6f5c52dce
            [lokaal] => Pratumplaats 2A, 3454 NA Utrecht
            [inactief] =>
            [inCompany] =>
            [opmerking] =>
            [bedrijfId] => 667ede91-20c1-41eb-ad98-0163730ff2cd
            [bedrijf] => Array
                (
                    [id] => 667ede91-20c1-41eb-ad98-0163730ff2cd
                    [naam] => Maximus Brouwerij
                    [tel1] => 030 737 0077
                    [email] =>
                    [website] =>
                    [bezoekadres] => Array
                        (
                            [adres1] => Pratumplaats 2a
                            [adres2] =>
                            [adres3] =>
                            [adres4] =>
                            [postcode] => 3454 NA
                            [plaats] => De Meern
                            [landCode] => NL
                            [land] => Array
                                (
                                    [code] => NL
                                    [naam] => Nederland
                                )
                        )
                )
            [contactpersoonId] =>
            [contactpersoon] =>
        )
)
 */

class TrainingComponent {

    public function __construct(
        public string       $id,
        public string       $code,
        public string       $name,
        public CourseFormat $course_format,   // e.g. 'Klassikaal', 'E-learning'
        public int          $sequence_number, // e.g. 7
        public ?string      $location,        // e.g. 'Maximus Brouwerij'
        public ?string      $address,         // e.g. 'Kalverstraat 2a'
        public ?string      $zipcode,         // e.g. '1234 AB'
        public ?string      $city,            // e.g. 'Amsterdam'
        public ?string      $date,            // e.g. '2025-06-06'
        public ?string      $start_time,      // e.g. '09:30:00'
        public ?string      $end_time,        // e.g. '17:00:00',
    ) {
    }

    public static function from_array(array $data): self {
//        error_log("TrainingComponent::from_array called with data: ");
//        error_log(print_r($data, true));

        $collection_data = collect([$data]);
        return new self(
            id: $data['id'],
            code: $data['code'],
            name: $data['naam'],
            course_format: CourseFormat::fromString($data['lesvormId'] ?? 'elearning'),
            sequence_number: intval($data['volgnummer'] ?? 0),
            location: firstNonEmpty($collection_data->pluck('locatie.bedrijf.naam')),
            address: firstNonEmpty($collection_data->pluck('locatie.bedrijf.bezoekadres.adres1')),
            zipcode: firstNonEmpty($collection_data->pluck('locatie.bedrijf.bezoekadres.postcode')),
            city: firstNonEmpty($collection_data->pluck('locatie.bedrijf.bezoekadres.plaats')),
            date: $data['datum'],
            start_time: $data['tijdVan'],
            end_time: $data['tijdTot']);
    }
}