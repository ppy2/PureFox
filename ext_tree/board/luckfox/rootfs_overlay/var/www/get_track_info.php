function updateTrackInfo() {
  fetch('get_track_info.php')
    .then(response => response.json())
    .then(data => {
      let info = "";
      // Проверяем оба варианта ключей для исполнителя и названия
      let title = data.Title || data.title || "";
      let artist = data.Artist || data.artist || "";
      let album = data.Album || data.album || "";
      if (title || artist) {
        if (artist) {
          info += artist;
        }
        if (title) {
          info += (artist ? " — " : "") + title;
        }
        if (album) {
          info += " (" + album + ")";
        }
      }
      document.getElementById("track-info").textContent = info;
    })
    .catch(err => {
      document.getElementById("track-info").textContent = "";
    });
}
setInterval(updateTrackInfo, 5000);
updateTrackInfo();

