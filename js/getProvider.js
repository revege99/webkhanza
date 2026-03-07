document.addEventListener("DOMContentLoaded", function () {

    const elNoKartu = document.getElementById("noKartu");
    if (!elNoKartu) return;

    const noKartu = elNoKartu.value.trim();
    if (!noKartu) return;

    fetch(`php/getProvider.php?jenis=noka&nomor=${encodeURIComponent(noKartu)}`)
    .then(response => response.json())
    .then(res => {

        if (!res.status) {
            alert("Gagal ambil data peserta: " + res.message);
            return;
        }

        console.log("Response BPJS:", res);

        const data = res.data;

        // 🔹 Ambil provider utama peserta
        const provider = data?.kdProviderPst;

        if (provider?.kdProvider) {

            document.getElementById("kdProvider").value = provider.kdProvider;

            // Kalau ada field nama provider
            const nmProviderEl = document.getElementById("nmProvider");
            if (nmProviderEl) {
                nmProviderEl.value = provider.nmProvider || "";
            }

        } else {
            alert("Provider peserta tidak ditemukan.");
        }

        // 🔹 Optional: Validasi status aktif
        if (!data.aktif) {
            alert("Peserta tidak aktif: " + data.ketAktif);
        }

    })
    .catch(err => {
        console.error("Fetch error:", err);
        alert("Terjadi kesalahan saat koneksi ke server");
    });

});