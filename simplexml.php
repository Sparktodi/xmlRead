<?php
function parseFile($url)
{
    if (!file_exists('parse.xml')) {
        $file = fopen('parse.xml', 'w');
        $options = array(
            CURLOPT_FILE => $file,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $url,
            CURLOPT_ENCODING => '',
            CURLOPT_TIMEOUT => 0,
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $return = curl_exec($ch);

        if ($return === false) {
            return curl_error($ch);
        } else {
            echo "Файл скачен";
            curl_close($ch);
            fclose($file);
        }
    }

    $xml = simplexml_load_file('parse.xml');

    // Переменная для подсчета квартир в Ярославле
    $countFlatsYaroslavl = 0;
    // Массив для хранения цен квартир в Минске
    $prices = [];
    //Выражение для поиска кв без животных
    $regex = '/(\b(?:без|не|нельзя|запрещено|запрещены|невозможно)\b(?:\s+\w+){0,3}\s+(?:животн\w+|питомц\w+))|((?:животн\w+|питомц\w+)(?:\s+\w+){0,3}\s+\b(?:не\s+(?:разрешены|допускаются)|запрещены|нельзя)\b)/iu';
    // Переменная для подсчета квартир в Москве с животными
    $countFlatsWithPets = 0;
    //Счетчик
    $processedNodes = 0;

    foreach ($xml->offer as $offer) {
        $category = (string)$offer->category;

        if ($category === 'flat') {
            $localityName = (string)$offer->location->{"locality-name"};
            switch ($localityName) {
                case 'Ярославль':
                    $countFlatsYaroslavl++;
                    break;
                case 'Минск':
                    $prices[] = (float)$offer->price->value;
                    break;
                case 'Москва':
                    $description = (string)($offer->description);
                    if (strlen($description) > 0) {
                        if (!preg_match($regex, $description)) {
                            $countFlatsWithPets++;
                        }
                    }
                    break;
            }
        }
        $processedNodes++;
        echo "\rПрочитано: " . $processedNodes;
    }


    sort($prices);
    $count = count($prices);
    $middle = (int)($count / 2);

    if ($count % 2 === 0) {
        $middlePrice = ($prices[$middle - 1] + $prices[$middle]) / 2;
    } else {
        $middlePrice = $prices[$middle];
    }

    echo "Количество квартир в Ярославле:" . $countFlatsYaroslavl . "\n";
    echo "Медианная цена аренды квартиры в Минске:" . $middlePrice . "\n";
    echo "Количество квартир в Москве где можно животных:" . $countFlatsWithPets . "\n";
}

parseFile('https://static.sutochno.ru/doc/files/xml/yrl_searchapp.xml');