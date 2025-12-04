<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Jira XML → HTML Dönüştürücü</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- 📱 mobil için -->
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .upload-box {
      border: 2px dashed #0d6efd;
      border-radius: 10px;
      padding: 25px;
      text-align: center;
      background: white;
      transition: 0.3s;
      cursor: pointer;
    }
    .upload-box:hover { background: #f1f7ff; }
    .upload-box input[type=file] { display: none; }

    /* 📱 Mobil için optimize */
    @media (max-width: 576px) {
      .upload-box { padding: 15px; }
      .upload-box i { font-size: 2rem !important; }
      h2 { font-size: 1.3rem; }
      button { font-size: 0.9rem; padding: 10px; }
    }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7 col-md-9 col-sm-12">
      <div class="card shadow-lg">
        <div class="card-body">
          <h2 class="text-center text-primary mb-4">Jira XML → HTML Dönüştürücü</h2>

          <!-- Tek dosya seç -->
          <form id="singleForm" enctype="multipart/form-data">
            <label for="singleFile" class="upload-box w-100 mb-3">
              <i class="bi bi-file-earmark-text fs-1 text-primary"></i>
              <p class="mt-2 mb-0 fw-bold">Tek XML Dosyası Seç</p>
              <small class="text-muted">(Sadece 1 dosya)</small>
              <input id="singleFile" type="file" name="xmlfiles[]" accept=".xml">
            </label>
            <button type="submit" class="btn btn-primary w-100 mb-4">🚀 Dönüştür (Tek)</button>
          </form>

          <!-- Çoklu dosya seç -->
          <form id="multiForm" enctype="multipart/form-data">
            <label for="multiFile" class="upload-box w-100 mb-3 border-success">
              <i class="bi bi-folder2-open fs-1 text-success"></i>
              <p class="mt-2 mb-0 fw-bold">Birden Fazla XML Seç</p>
              <small class="text-muted">(Ctrl / Shift ile çoklu seçim yapabilirsiniz)</small>
              <input id="multiFile" type="file" name="xmlfiles[]" multiple accept=".xml">
            </label>
            <button type="submit" class="btn btn-success w-100">🚀 Dönüştür (Çoklu)</button>
          </form>

          <hr><footer class='text-center mt-5 mb-3 text-muted'>
  Coded by <strong><a href="https://github.com/mbiyiktr">mbiyiktr</a></strong>
</footer>
          <!-- Progress ve sonuç alanı -->
          <div id="progressArea" class="mt-4" style="display:none;">
            <div class="progress mb-2" style="height: 25px;">
              <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                   style="width: 0%;">0%</div>
            </div>
            <div id="result" class="alert mt-3 d-none"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function uploadFiles(formId) {
    const form = document.getElementById(formId);
    form.addEventListener("submit", function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "convert.php", true);

        // Progress event
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                let percent = Math.round((e.loaded / e.total) * 100);
                document.getElementById("progressArea").style.display = "block";
                const bar = document.getElementById("progressBar");
                bar.style.width = percent + "%";
                bar.innerText = percent + "%";
            }
        };

xhr.onload = function() {
    const result = document.getElementById("result");
    result.classList.remove("d-none", "alert-success", "alert-danger");

    if (xhr.status === 200) {
        result.classList.add("alert-success");
        result.innerHTML = xhr.responseText;

        // Dosya seçilmemişse progress bar gizle
        if (result.innerHTML.includes("Hiç dosya seçilmedi!")) {
            const bar = document.getElementById("progressBar");
            bar.style.display = "none"; // komple gizle
            bar.style.width = "0%";
            bar.innerText   = "0%";
        }

    } else {
        result.classList.add("alert-danger");

        // Sunucudan özel hata mesajı dönerse onu göster
        if (xhr.responseText) {
            result.innerHTML = xhr.responseText;

            // Hata anında progress bar’ı da sıfırla
            const bar = document.getElementById("progressBar");
            bar.style.display = "none";
            bar.style.width = "0%";
            bar.innerText   = "0%";

        } else {
            result.innerText = "Bir hata oluştu.";
        }
    }
};


        xhr.send(formData);
    });
}

// Tek ve çoklu form için ayrı ayrı bağla
uploadFiles("singleForm");
uploadFiles("multiForm");
</script>
</body>
</html>
