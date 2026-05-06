<!-- Plan Seçim Kartları -->
<div class="row m-4">
    <div class="col-md-6">
        <div class="card border-primary text-center">
            <div class="card-body">
                <h3>Öğrenci Planı</h3>
                <p class="text-muted">Aylık 49.90 TL</p>
                <form action="premium_odeme.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="plan" value="ogrenci">
                    <label>Öğrenci Belgesi (PDF/JPG):</label>
                    <input type="file" name="belge" class="form-control mb-3" required>
                    <button type="submit" class="btn btn-outline-primary">Ödemeye Geç</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-success text-center">
            <div class="card-body">
                <h3>Yetişkin Planı</h3>
                <p class="text-muted">Aylık 99.90 TL</p>
                <form action="premium_odeme.php" method="POST">
                    <input type="hidden" name="plan" value="yetiskin">
                    <button type="submit" class="btn btn-outline-success mt-4">Ödemeye Geç</button>
                </form>
            </div>
        </div>
    </div>
</div>