<?php
$uploadDir = __DIR__ . '/uploads/';
$outputDir = __DIR__ . '/output/';

// uploads klasörü yoksa oluşturmayı dene
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        http_response_code(500);
        die("❌ uploads klasörü oluşturulamadı!");
    }
}
// hala yazılamıyorsa
if (!is_writable($uploadDir)) {
    http_response_code(500);
    die("❌ uploads klasörüne yazma izni yok!");
}

// output klasörü yoksa oluşturmayı dene
if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0777, true)) {
        http_response_code(500);
        die("❌ output klasörü oluşturulamadı!");
    }
}
// hala yazılamıyorsa
if (!is_writable($outputDir)) {
    http_response_code(500);
    die("❌ output klasörüne yazma izni yok!");
}

// Dosya var mı?
if (!isset($_FILES['xmlfiles']) || count($_FILES['xmlfiles']['name']) == 0 || $_FILES['xmlfiles']['name'][0] == "") {
    die("⚠️ Hiç dosya seçilmedi!");
}
$convertedFiles = []; // dönüştürülen dosyaları burada tut
$failedFiles = [];  // hatalıları burada tut

$totalFiles = count($_FILES['xmlfiles']['name']);

for($i=0; $i<$totalFiles; $i++){
    if ($_FILES['xmlfiles']['error'][$i] !== UPLOAD_ERR_OK) {
	$failedFiles[] = $_FILES['xmlfiles']['name'][$i] . " (upload hatası)";
   continue; // hatalı dosyayı atla
    }

    $tmpName = $_FILES['xmlfiles']['tmp_name'][$i];
    $name = basename($_FILES['xmlfiles']['name'][$i]);
    $xmlPath = $uploadDir . $name;

   // Uzantı kontrolü
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext !== 'xml') {
        $failedFiles[] = "❌ $name (sadece .xml dosyalarına izin verilir)";
        continue;
    }
 
//  Dosya boyutu kontrolü (örnek: max 10MB)
if (filesize($tmpName) > 5 * 1024 * 1024) {
    $failedFiles[] = "❌ $name (dosya çok büyük, 10MB üstü yasak)";
    continue;
}

// İçerik gerçekten XML mi? (Parser ile kontrol)
libxml_use_internal_errors(true);
$xmlTest = simplexml_load_file($tmpName);
if ($xmlTest === false) {
        $errors = libxml_get_errors();
$failedFiles[] = "❌ $name (XML içeriği geçersiz veya bozuk)";
       libxml_clear_errors();
 continue;
}


if (!@move_uploaded_file($tmpName, $xmlPath)) {
    $failedFiles[] = "❌ $name Upload klasörüne kaydedilemedi. (Yazma izni yok olabilir)";
    continue;
}

	
	  // Dosya gerçekten var mı?
    if (!file_exists($xmlPath)) {
        $failedFiles[] = $name . " ❌ Dosya upload klasörüne kaydedilemedi: $xmlPath";
        continue;
    } elseif (filesize($xmlPath) === 0) {
        $failedFiles[] = $name . " ❌ Dosya boş kaydedildi: $xmlPath";
        continue;
    }

      $xml = @simplexml_load_file($xmlPath);
    if ($xml === false || !isset($xml->channel->item)) {
        $failedFiles[] = $name . " XML dosyası yüklendi ama dönüştürme başarısız. Yüklenen dosya XML değil veya beklenen <channel><item> yapısı yok!";
        continue;
    }

    $item = $xml->channel->item;

    // Ana alanlar
    $title = html_entity_decode((string)$item->title);
    $link = (string)$item->link;
    $summary = (string)$item->summary;
    $key = (string)$item->key;
    $projectName = (string)$item->project;
    $projectId = (string)$item->project['id'];
    $projectKey = (string)$item->project['key'];
    $description = html_entity_decode((string)$item->description);

    $typeName = (string)$item->type;
    $typeIcon = (string)$item->type['iconUrl'];

    $priorityName = (string)$item->priority;
    $priorityIcon = (string)$item->priority['iconUrl'];

    $statusName = (string)$item->status;
    $statusIcon = (string)$item->status['iconUrl'];
    $statusDesc = (string)$item->status['description'];

    $resolution = (string)$item->resolution;
    $assignee = (string)$item->assignee;
    $reporter = (string)$item->reporter;
    $created = (string)$item->created;
    $updated = (string)$item->updated;
    $votes = (string)$item->votes;
    $watches = (string)$item->watches;

    // Issue Detayları
    $issueInfoHtml = "
      <h2 class='mt-4'>Talep Detayları</h2>
      <ul class='list-group mb-3'>
        <li class='list-group-item'><strong>Anahtar:</strong> <a href='$link' target='_blank'>$key</a></li>
        <li class='list-group-item'><strong>Proje:</strong> $projectName <small class='text-muted'>(ID: $projectId / Key: $projectKey)</small></li>
        <li class='list-group-item'><strong>Özet:</strong> $summary</li>
        <li class='list-group-item'><strong>Tip:</strong> <img src='$typeIcon' alt='' style='height:16px;'> $typeName</li>
        <li class='list-group-item'><strong>Öncelik:</strong> <img src='$priorityIcon' alt='' style='height:16px;'> $priorityName</li>
        <li class='list-group-item'><strong>Durum:</strong> <img src='$statusIcon' alt='' style='height:16px;' title='$statusDesc'> $statusName</li>
        <li class='list-group-item'><strong>Çözüm:</strong> $resolution</li>
        <li class='list-group-item'><strong>Atanan:</strong> $assignee</li>
        <li class='list-group-item'><strong>Raporlayan:</strong> $reporter</li>
        <li class='list-group-item'><strong>Oluşturma:</strong> $created</li>
        <li class='list-group-item'><strong>Güncelleme:</strong> $updated</li>
        <li class='list-group-item'><strong>Oy sayısı:</strong> $votes / <strong>İzleyici sayısı:</strong> $watches</li>
      </ul>
    ";

    // Custom Fields
    $customFieldsHtml = "<h2 class='mt-4'>Özel Alanlar</h2><ul class='list-group mb-3'>";
    if (isset($item->customfields->customfield)) {
        foreach ($item->customfields->customfield as $cf) {
            $cfName = (string)$cf->customfieldname;
            $cfValues = [];

            if (isset($cf->customfieldvalues->customfieldvalue)) {
                foreach ($cf->customfieldvalues->customfieldvalue as $val) {
                    $attrs = $val->attributes(); 
                    $cascade = isset($attrs['cascade-level']) ? (string)$attrs['cascade-level'] : '';

                    // Eğer cascade-level=1 varsa onu al
                    if ($cascade === "1") {
                        $cfValues[] = trim((string)$val);
                    }
                }

                // Eğer cascade-level=1 bulunamadıysa, diğer değerleri al
                if (empty($cfValues)) {
                    foreach ($cf->customfieldvalues->customfieldvalue as $val) {
                        $cfValues[] = trim((string)$val);
                    }
                }
            }

            // Değer yoksa "(boş)" yaz
            $cfValueStr = count($cfValues) > 0 ? implode(", ", $cfValues) : "(boş)";
            $customFieldsHtml .= "<li class='list-group-item'><strong>$cfName:</strong> $cfValueStr</li>";
        }
    }
    $customFieldsHtml .= "</ul>";



    // Final HTML
    $html = "<!DOCTYPE html><html lang='tr'><head><meta charset='UTF-8'>
    <title>$title</title>
    <link href='font/bootstrap.min.css' rel='stylesheet'>
    </head><body class='bg-light'>
    <div class='container py-5'>
      <div class='card shadow'>
        <div class='card-body'>
          <h1 class='text-primary'><a href='$link' target='_blank'>$title</a></h1>
          $issueInfoHtml
          <h2 class='mt-4'>Açıklama</h2>
          <div class='border rounded p-3 bg-white'>$description</div>
          $customFieldsHtml
        </div>
      </div>
    </div>
    </body></html>";
	
	
    $outputFile = $outputDir . pathinfo($name, PATHINFO_FILENAME) . ".html";
	    file_put_contents($outputFile, $html);
    // Listeye ekle
    $convertedFiles[] = basename($outputFile);
   // file_put_contents($outputDir . pathinfo($name, PATHINFO_FILENAME) . ".html", $html);
}

// En sonda listele
if (!empty($convertedFiles)) {
    echo "✅ Dönüştürme tamamlandı!<br>Oluşturulan dosyalar:<br><ul>";
    foreach ($convertedFiles as $cf) {
        echo "<li><a href='output/$cf' target='_blank'>$cf</a></li>";
    }
    echo "</ul>";
 } 
if (!empty($failedFiles)) {
    http_response_code(400); echo "⚠️ Hatalı dosyalar:<br><ul>";
    foreach ($failedFiles as $ff) {
        echo "<li>$ff</li>";
    }
    echo "</ul>";
}

if (empty($convertedFiles) && empty($failedFiles)) {
    http_response_code(400); echo "⚠️ Hiç dosya işlenemedi.";
}
 



