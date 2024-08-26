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

    $reader = new XMLReader();
    $reader->open('parse.xml');

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

    while ($reader->read()) {
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->localName == 'offer') {

            $doc = new DOMDocument();
            $doc->loadXML($reader->readOuterXML());
            $category = $doc->getElementsByTagName('category')->item(0)->nodeValue;

            if ($category === 'flat') {
                $localityName = $doc->getElementsByTagName('locality-name')->item(0)->nodeValue;
                if ($localityName === 'Ярославль') {
                    $countFlatsYaroslavl++;
                } elseif ($localityName === 'Минск') {
                    $prices[] = (float)$doc->getElementsByTagName('value')->item(0)->nodeValue;
                } elseif ($localityName === 'Москва') {
                    $description = $doc->getElementsByTagName('description');
                    if (!$description->length == 0) {
                        if (!preg_match($regex, $description->item(0)->nodeValue)) {
                            $countFlatsWithPets++;
                        }
                    }
                }
            }

            $processedNodes++;
            echo "\rПрочитано: " . $processedNodes;
        }
    }

    $reader->close();

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

