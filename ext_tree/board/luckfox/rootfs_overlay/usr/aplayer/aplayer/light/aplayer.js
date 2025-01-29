var coeff=1.0;   // Коэффициент масштабирования веб-интерфейса
var CurrentPlaylist = null; //Выбранный плейлист
var CurrentSong = null; // Выбранный трек, индекс
var PlayingSong = null; // Воспроизводимый трек, индекс
var Albums = new Object(); // Список альбомов плейлиста  
var CurrentAlbum = null; //Выбранный альбом, числовой индекс
var PlayingAlbum = 0; //Воспроизводимый альбом, числовой индекс
var Volume = null;  // Громкость
var Position = null;  // Позиция воспроизведения
var Playing = false; // Состояние воспроизведения
var CurrentSongs = null; // Список песен выбранного альбома
var CurrentTime = null; // Текущее время воспроизведения в секундах
var PlayTimerId = null; // идентификатор таймера воспроизведения

var Playlists = null;  // Список плейлистов
var PlaylistIndex = 0; // Последний выбранный плейлист
var PlaylistIndexNew = 0; // Выбранный в списке плейлист
var Paused = false; // Состояние паузы
var ModePlaylists = false; // Выводится список плейлистов
var ChangePosition = false; // Обрабатывется позиционирование в треке
var ViewPictures = true; //  Режим отображения картинок
var PlayingLen = null; // Длительность воспроизводимого трека
var PlayingName = null;  // Воспроизводимый трек, название
var PicturesInterval = 20;  // Интервал обновления картинок, сек.
var IsSafari = navigator.userAgent.indexOf('Safari') != -1 && navigator.userAgent.indexOf('Chrome') == -1 && navigator.userAgent.indexOf('Android') == -1;
var timestamp = new Date().getTime();
var SavedAlbumIndex = 0;
var SavedSongIndex = 0;
var bFirst = false;
var OldViewPictures = true;
var FlagRadio = false;
var AddMode = false;
var RadioMode = false;
var RadPcs = [];
var IndRadPcs = 0;
var ShowRPcs = true;
var DirsListPos = 0;
var isMidori = navigator.userAgent.indexOf('Midori/0.5') > -1;
var hasHGlass = false;
// Инициализация при загрузке
$.getJSON('?GetAlbums', ParseAlbums);
window.onblur = OnBlur;
window.onfocus = OnFocus;
SetTimer();

function StopTimer()
{
    if (IsSafari)
    	return;
    if (PlayTimerId != null)
    {
        clearInterval(PlayTimerId);
        PlayTimerId = null;
    }
}

function SetTimer()
{
    StopTimer();
    if (PlayTimerId == null)
        PlayTimerId = setInterval('PlayTimer()', 1000);
	}

function OnBlur()
{
    StopTimer();
}

function OnFocus()
{

   if (coeff!=1.0)
   {
     var elm = document.getElementById('all');
     if (navigator.userAgent.indexOf('Firefox')!=-1) elm.style.boxShadow='none';
     elm.style.webkitTransform = elm.style.msTransform =  elm.style.mozTransform =   elm.style.transform = 'scale('+coeff+')';
   }
   UpdateState();
   if(PlayTimerId == null)
        SetTimer();
}

function StatusClick() {
    if (ModePlaylists == true) {
        PlaylistIndexNew = -1;
        OnSelectPlaylist(false);
        $('#Openlist').css('display', 'none');
    }
}

function OnPictureMode() {
    if (ViewPictures) {

        $('#ImgBlock').css('display', 'none');
        $('#Playlists').removeClass('narrowList');
        $('#Songs').removeClass('narrowList');
        $('#SongsBlock').removeClass('narrowList');
        $('#Playlists').addClass('wideList');
        $('#Songs').addClass('wideList');
        $('#SongsBlock').addClass('wideList');
        ViewPictures = false;
    }
    else {
        $('#Playlists').removeClass('wideList');
        $('#Songs').removeClass('wideList');
        $('#SongsBlock').removeClass('wideList');
        $('#Playlists').addClass('narrowList');
        $('#Songs').addClass('narrowList');
        $('#SongsBlock').addClass('narrowList');
        $('#ImgBlock').css('display', '');
        $('#Image').attr('src', '?GetImage&pl=' + PlaylistIndex + '&image=' + CurrentAlbum + '&time=' + (new Date()).getTime());
        ViewPictures = true;
    }
}

function SetPlaylistSelColor(down)
{
    $.each($('#Playlists').children(), function (ind2, val) {
        if (ind2 == PlaylistIndexNew)
        {
            if(down == true)
                 $(val).css('background-color', '#ccc');
            else
                 $(val).css('background-color', '#888');
        }
        else
            $(val).css('background-color', '#333');
    });
}

function OnSelectPlaylist_(data)
{
    Playlists = data.Playlists;   
    $('#Playlists').empty();
    if (Playlists != null && Playlists.length > 0)
    {
         $.each(Playlists, function (ind, val) { 
	   var val_ = (ind == 0) ? 'Last Playlist' : val;
          if(window.location.toString().charAt(7) =='l')
          {
				val_ = val_.replace("&#8862;","<b>+</b>");
				val_ = val_.replace("&#8863;","<b>-</b>");				          
          } 
	 $('#Playlists').append('<div style="text-align:left;padding:5px;border-bottom:2px solid #555;margin-left:-5px;" onclick="SelectPlaylist(' + ind + ')">' + val_ + '</div>'); }); 
    }
    SetPlaylistSelColor(false);
    $('#bOpen').attr('src', 'img/openp.png');
    $('#Albums').css('visibility', 'hidden');
    $('#Songs').css('display', 'none');
    $('#Playlists').css('display', '');
    $('#Openlist').css('display', 'block');    
    $('html, body').scrollTop(DirsListPos);
}

 function MakeHourglass()
 {
     $('<img id="Hourglass" src="img/hourglass.gif">').appendTo('#Topcontrols');
     hasHGlass = true;
 }

function RemoveHourglass()
{
    $('#Hourglass').remove();
    hasHGlass = false;
 }

function ReturnToSongs()
{
    $('html, body').scrollTop(0);
    $("#Input").css('display', 'none');
    $("#Filter").css('display', 'block');
    $("#Topcontrols").css('display', 'block');    
    if (OldViewPictures)
        OnPictureMode();
    if (PlaylistIndex != -1) {
          $('#Albums').empty();
          $('#Songs').empty();
    }
    $('html, body').scrollTop(0);   
}

function OnSelectPlaylist(add)
{
		if(hasHGlass)
			return;
    if(RadioMode)
    {
        $('#bOpen').attr('src', 'img/openp.png')
        return;
    }
    if (ModePlaylists == false)
    {
      if (add)
         AddMode = true;
      else
         AddMode = false;
      PlaylistIndex = PlaylistIndexNew = -1;
      $('#Topcontrols').css('display', 'none');
      $('option').css('display', 'block');
      $.getJSON('?GetPlaylists&ind=-1', OnSelectPlaylist_);
      ModePlaylists = true;
      OldViewPictures = ViewPictures;
      if (ViewPictures)
          OnPictureMode();
      $('#Topcontrols').css('display', 'none');
    }
    else
    {
        DirsListPos =  $('html, body').scrollTop();
        ReturnToSongs();
        if (PlaylistIndexNew == null)
            PlaylistIndexNew = 0;
        PlaylistIndex = PlaylistIndexNew;
    
        if (PlaylistIndex != -1)
        {
              SetPlaylistSelColor(true);
              MakeHourglass();
              $.getJSON('?GetPlaylist&playlist=' + PlaylistIndex, ParseStd);
        }
        
        $('#bOpen').attr('src', 'img/openp.png')
        ModePlaylists = false;
        $('#Albums').css('visibility', 'visible');
        $('#Songs').css('display', '');
        $('#Playlists').css('display', 'none');
    }
}

function OnViewAlbum()
{
   if (!ViewPictures)
        OnPictureMode();
   if (!Playing)
        return;
    var trackinfo = $('#lRadio').text();
    var $tempinp = $("<input id='inptmp'>");
    $("body").append($tempinp);
    $tempinp.val(trackinfo);
    var tempinput=document.getElementById('inptmp');
    tempinput.focus();
    tempinput.setSelectionRange(0, tempinput.value.length);
    document.execCommand("copy");
    $tempinp.remove();	

   $('html, body').scrollTop(0);
   if(CurrentAlbum != PlayingAlbum)
   {
    CurrentAlbum = PlayingAlbum;
    $('#Albums')[0].selectedIndex = CurrentAlbum;
    if (Albums.Songs != null && Albums.Songs.length > 0)
    {
        $.getJSON('?GetSongs&album='+ CurrentAlbum, ParseSongs);
    }
   }
   UpdateRadioPicture();
}

function ProgressChange(pos)
{
    if (!Playing)
        document.getElementById('progress').value = 0;
    else
    {
        ChangePosition = true;
        PositionCommand(pos);
        CurrentTime = PlayingLen / 1000 * pos / 100;
    }
}

function VolumeChange(vol)
{
    ChangePosition = true;
    if(vol > 100)
	vol = 100;
    else
	if(vol < 0)
 		vol = 0;	
    VolumeCommand(vol);
}

function GetLenText(len) // time format 0:00:00
{
    var Playtime = len / 1000; // sec
    var result = "";
    if(Playtime >= 3600)
				{
          result = result + Math.round(Playtime/3600 - 0.5) + ':';
					Playtime=Playtime%3600;
				}
				if(Playtime >= 600)
					{
           result = result + Math.round(Playtime/60 - 0.5);
					  }
				else
           result = result + '0' + Math.round(Playtime/60 - 0.5);
				result = result  + ':';
				var ost = Math.round(Playtime % 60 - 0.5);
				if(ost > 9)
					 result = result  + ost;
				else
					 result = result  + '0' + ost;
		return result;
}

function ParseSongs(data)
{
    var Songs = data.Songs;
    CurrentSongs = Songs;
    $('#Songs').empty();
    if (Songs != null && Songs.length > 0)
    {
        $.each(Songs, function (ind, val) { $('#Songs').append('<div style="width:100%; padding:5px; border-bottom:1px solid #555; white-space:nowrap; overflow:hidden; text-overflow: ellipsis" onclick="PlayCommand2(' + ind + ');SelectSong(' + ind + ')">▪ ' + val + '</div>'); }); // ondblclick="SelectSong(' + ind + ')"
         if(Playing && CurrentAlbum == PlayingAlbum)
         {
             SelectSong(PlayingSong);
         }
         else
         if(bFirst)
               SelectSong(SavedSongIndex);
         else
            SelectSong(-1);
    }
    else
        CurrentSong = null;
    $('html, body').scrollTop(0);
}

function ParseAlbums(data)
{  
    $('#bOpen').attr('src', 'img/openp.png')
    ModePlaylists = false;
    $('#Albums').css('visibility', 'visible');
    $('#Songs').css('display', '');
    $('#Playlists').css('display', 'none');
    $('#Topcontrols').css('display', 'block');
    Albums.Albums = data.Albums;
    Albums.Authors = data.AuthorList;
    Albums.Songs = data.Songs;
    Albums.AlbumsS = data.AlbumList;
    Albums.Years = data.YearList;
    Playlists = data.Playlists;
    SavedAlbumIndex = data.SavedAlbumIndex;
    SavedSongIndex = data.SavedSongIndex;
    if(data.RadioMode)
    {
        RadioMode = data.RadioMode;
        $('#bRadio').attr('src', 'img/radioa.png');   
        $.each(Albums.Albums, function (ind, val) { var pos = val.lastIndexOf('[');if(pos > 0) Albums.Albums[ind]=val.substring(0, pos-1);});    
    }

    $('#Albums').empty();
    $('#Playlists').empty();
    $('#Songs').empty();
    if (Albums.Albums != null && Albums.Albums.length > 0) {
        CurrentAlbum = SavedAlbumIndex;
        $('#Image').attr('src', '?GetImage&pl=' + PlaylistIndex + '&image='+ CurrentAlbum + '&time=' + (new Date()).getTime());
        $.each(Albums.Albums, function (ind, val) { $('#Albums').append('<option value=' + ind + ' title="'+ val +'" style="font-size:14px;">▪ ' + val + '</option>'); });
        $("#Albums option[value='"+ SavedAlbumIndex  + "']").prop('selected', true);
        if (Albums.Songs != null && Albums.Songs.length > 0)
        {
            bFirst = true;
            ParseSongs(Albums.Songs[0]);
            bFirst = false;
        }
    }
    else {
            CurrentAlbum = null;
            $('#Image').attr('src', 'img/logo.png');
         }
    if (Playlists != null && Playlists.length > 0)
    {
        $.each(Playlists, function (ind, val) { var val_=(ind==0)? 'Last Playlist': val;$('#Playlists').append('<div style="padding:5px;height:24px;line-height:32px;text-align:left;border-bottom:1px solid #555;"  onclick="SelectPlaylist('+ind+');OnSelectPlaylist();">'+val_+'</div>'); });
      //  SelectPlaylist(PlaylistIndex);
    }
    SelectSong(SavedSongIndex);
    UpdateState();
}

function ParseStd(data)
{
    var res = data.Result;
    if(res != "OK")
     DisplayError(res)
    UpdateState();
}

function PlayTimer() 
{
    if (ChangePosition)
        return;
     var len = PlayingLen;
     if(Playing)
      {
         if (!Paused)
         {
             CurrentTime = CurrentTime + 1;
             // Pictures rotator             
             if (!FlagRadio && Math.floor(CurrentTime) % PicturesInterval == 0 && CurrentAlbum == PlayingAlbum && ViewPictures)
             {
                 $('#Image').attr('src', '?GetImage&pl=' + PlaylistIndex + '&image=' + CurrentAlbum + '&time=' + (new Date()).getTime());
             }

         }
         document.getElementById('progress').value = CurrentTime * 1000 / len * 100;
         $('#lTime').text(GetLenText(CurrentTime * 1000)+' /');
          current = new Date().getTime();
           if (current - timestamp > 2500) 
                 len = 0;
           timestamp = current;                      
     }             
    if(($('#lKhz').text() == '' || len /1000 - CurrentTime < 5 || CurrentTime < 5 || $('#progress').css('visibility') == 'hidden' ) &&Playing || Paused || hasHGlass)
        UpdateState();
     if(RadioMode && Playing &&  Math.floor(CurrentTime) % 10 == 0)
     {       
        $.getJSON('?GetRadio', ParseRadio);
        if(Math.floor(CurrentTime) % PicturesInterval == 0 && CurrentAlbum == PlayingAlbum && ViewPictures)
        {
            UpdateRadioPicture();
        }
     }
}

function UpdateRadioPicture()
{    
    if(ViewPictures)
    {
        if(RadioMode && Playing && ShowRPcs && RadPcs.length > 0 &&  CurrentAlbum == PlayingAlbum) 
        {                    
           IndRadPcs = IndRadPcs % RadPcs.length;   
           var img = new Image();
	        img.src = RadPcs[IndRadPcs];
	        img.onload = function(){
	    	       if(img.width > 0) {
                  $('#Image').attr('src',img.src);
                }
            };    
            IndRadPcs = IndRadPcs + 1;
        }  
        else
        {
            $('#Image').attr('src', '?GetImage&pl=' + PlaylistIndex + '&image=' + CurrentAlbum + '&time=' + (new Date()).getTime());
        }
    }
}

function OnParseRadioPictures(data, textStatus, jqXHR)
{
	RadPcs.length = 0;
   IndRadPcs = 0;
   var pics = data.Pics; 
	if ( pics != null && pics.length > 0) 
   { 
    for(var i = 0; i <  pics.length; i++)
    {
      RadPcs.push(pics[i]);
    }
   }
    UpdateRadioPicture();
   
}

function ParseRadioPictures()
{  
   $.getJSON('?GetPictures', OnParseRadioPictures); 
}

function ParseRadio(data)
{
     var q = data.RadioTitle;
     var tx = q.trim();
	  	if(tx == '')
		{
	    RadPcs.length = 0;
	    IndRadPcs = 0;  
	    UpdateRadioPicture();
		return;         
		}
    var txt = '<br>'+q;
    if($('#lRadio').html() != txt) 
    {    
            $('#lRadio').html(txt);    
            if(ShowRPcs && ViewPictures)   
                ParseRadioPictures();
    }
}

function ParsePlay(data)
{
    CurrentTime = 0;
    if (PlayTimerId == null)
    	SetTimer();
    var res = data.Result;
    if (res != "OK")
    {
        DisplayError(res);
        Playing = false;
        $('#lTime').text('');
    }
    else
    {
        $('#lTime').text('00:00');
        Playing = true;
    }
    UpdateState();
}

function ParseStopPlay(data)
{
    var res = data.Result;
    if(res != "OK")
         DisplayError(res)
    Playing = false;

    PlayCommand();
}

function ChangeAlbum(obj)
{
	 if (hasHGlass)
      return;
  CurrentAlbum = $(obj).val();  
   $.getJSON('?GetSongs&album='+ CurrentAlbum, ParseSongs);
   $('#Image').attr('src', '?GetImage&pl=' + PlaylistIndex + '&image=' + CurrentAlbum + '&time=' + (new Date()).getTime()); 
}

function PlayCommand()
{
    if(CurrentAlbum == null || CurrentSongs == null || Playing || CurrentSongs.Length == 0 || hasHGlass)
        return;
    PlayingSong = CurrentSong;
    $.each($('#Songs').children(), function (ind2, val) { $(val).css('background-color', '#333'); });
    PlayingAlbum = CurrentAlbum;
     if(CurrentSong < 0 || CurrentSong > CurrentSongs.length -1)
        PlayingSong=CurrentSong = 0;
    $('#lRadio').text(''); 
    RadPcs.length = 0;
     $.getJSON('?Play&album='+ CurrentAlbum+'&song='+CurrentSong, ParsePlay);
}

function PlayCommand2(ind)
{
    CurrentSong = ind;
    $.each($('#Songs').children(), function (ind2, val) { $(val).css('background-color', '#333'); });
    if (Playing)
    {
        StopCommand(ParseStopPlay);
    }
    else
        PlayCommand();
}

 function ParsePath(data)
 {
    $('#lAlbum').html(data.Path);
 }

function SelectSong(ind)
{
    CurrentSong = ind;
    $.each($('#Songs').children(), function (ind2, val) {
        if (ind == ind2)
            $(val).css('background-color', '#399');
        else
            $(val).css('background-color', '#333');
    });
}

function SelectPlaylist(ind)
{
    DirsListPos =  $('html, body').scrollTop();    
    if (PlaylistIndexNew != ind)
    {
        PlaylistIndexNew = ind;
        SetPlaylistSelColor(false);
    }
    else
    {
        SetPlaylistSelColor(true);        
       $.getJSON('?GetPlaylists&ind=' + ind, OnSelectPlaylist_);
    }
}

function StopCommand(parser)
{
     $('#lTime').text('');
     $('#lTotalTime').text('');
     document.getElementById('progress').value = 0;
     $.getJSON('?Stop', parser);
}

function PositionCommand(pos)
{
    if (!Playing)
            return;
     $.getJSON('?Position&pos='+ pos.toString(), ParseStd);
}

function VolumeCommand(vol)
{
     $.getJSON('?SetVolume&volume='+ vol.toString(), ParseVolume);
}

function PauseCommand()
{
    if (!Playing)
            return;
     $.getJSON('?Pause', ParseStd);
}

function NextCommand()
{
       if (!Playing)
            return;
      document.getElementById('progress').value = 0;
    //  Paused = false;
      $.getJSON('?Next', ParsePlay);
}

function PrevDown()
{
   $('#bPrev').attr('src', 'img/preva.png')
}

function PrevUp()
{
   $('#bPrev').attr('src', 'img/prevp.png')
}

function StopDown()
{
   $('#bStop').attr('src', 'img/stopa.png')
}

function StopUp()
{
   $('#bStop').attr('src', 'img/stopp.png')
}

function PlayDown()
{
 //  if(!Playing)
 //   $('#bPlay').attr('src', 'img/playa.png')
}

function PlayUp()
{
   if(!Playing)
     $('#bPlay').attr('src', 'img/playp.png')
}

function PauseDown()
{
}

function PauseUp()
{
}

function NextDown()
{
   $('#bNext').attr('src', 'img/nexta.png')
}

function NextUp()
{
   $('#bNext').attr('src', 'img/nextp.png')
}

function OpenDown()
{
   $('#bOpen').attr('src', 'img/openp.png')
   $('#Openlist').css('display', 'none');//view Title
}

function OpenUp()
{
   if (ModePlaylists == false)
    $('#bOpen').attr('src', 'img/openp.png')
}

function PrevCommand()
{
    if (!Playing)
            return;
     document.getElementById('progress').value = 0;
     $.getJSON('?Prev', ParsePlay);
}

function HideError()
{
    $('#lError').css('display', 'none');
}

function DisplayError(error)
{
    $('#lError').text(error);
    $('#lError').css('display', 'block');
    setTimeout("HideError()", 3000);
}

function ParseVolume(data)
{
   ChangePosition = false;
}

function ParseState(data)
{
    ChangePosition = false;
     if (data.Scanning) {
       if (!hasHGlass)
          MakeHourglass();
       $.getJSON('?GetPath', ParsePath);
      }
    else {
      if (hasHGlass) {
          RemoveHourglass();
          $.getJSON('?GetAlbums', ParseAlbums);
      }
   }
    if(data.LastError.length > 0)
        DisplayError(data.LastError);
    if(Volume == null)
     document.getElementById('volume').value = Volume = data.Volume;  
     if(RadioMode != data.RadioMode)
     {            
         RadioMode = data.RadioMode;
         if(RadioMode)                        
             $('#bRadio').prop('src', 'img/radioa.png');                                 
         else            
             $('#bRadio').prop('src', 'img/radiop.png')                                    
      }    
    if(RadioMode && (Boolean(ShowRPcs) != Boolean(data.RadioPictures)))
    {                
     ShowRPcs = data.RadioPictures;     
     UpdateRadioPicture();
    }
    if (!data.Playing)
    {
        Playing = false;
        $('#lArtist').text('');
        $('#lArtist').attr('title','');
        if(!data.Scanning)
          $('#lAlbum').text('');
        $('#lAlbum').attr('title','');
        $('#lYear').text('');
        $('#lNum').text('');
        $('#lSong').text('');
        $('#lSong').attr('title','');
        $('#lRadio').text('');
        $('#lKbps').text('');
        $('#lKhz').text('');
        $('#lBit').text('');
        $('#lCh').text('');
        $('#lLength').text('');
        $('#lTime').text('');
        $('#bPlay').attr('src', 'img/playp.png')
        $('#bPause').attr('src', 'img/pausep.png')
        CurrentTime = 0;
        PlayingLen = 0;
        PlayingName = "";
        document.getElementById('progress').value = 0;
    }
    else
    {
        var new_alb = PlayingAlbum != data.Album;
       Playing = true;
       if(PlayTimerId == null)
             SetTimer();
        PlayingAlbum = data.Album;
        PlayingSong = data.Song;
        if (PlayingAlbum == CurrentAlbum) {
            SelectSong(PlayingSong);
        }
        else {            
            if (new_alb)
            {
                OnViewAlbum();
                SelectSong(PlayingSong);
            }
            else
                SelectSong(-1);
        }
        $('#lArtist').text(Albums.Authors[data.Album]);
        $('#lArtist').attr('title', $('#lArtist').text());
        $('#lAlbum').text(Albums.AlbumsS[data.Album]);
        $('#lAlbum').attr('title', $('#lAlbum').text());
        $('#lYear').text(Albums.Years[data.Album]);
        $('#lNum').text((data.Song+1).toString()+'. ');
        PlayingName = data.PlayingName;
        $('#lSong').text(PlayingName);
        $('#lSong').attr('title', $('#lSong').text());
        if (data.Freq > 0 && data.Bitrate > 0) 
        {
            $('#lKbps').text(data.Bitrate);
            if (data.Freq > 1000000) {
                $('#lKhz').text(data.Freq / 1000000);
                $('#khz').text('MHz');
            }
            else
             {
                $('#lKhz').text(data.Freq/1000);
                $('#khz').text('kHz');
            }
        }
        else
        {
            $('#lKbps').text('');
            $('#lKhz').text('');
        }  
        $('#lBit').text(data.Bps);
        $('#lCh').text(data.Ch);
        PlayingLen = data.PlayingLen;
        $('#lLength').text(GetLenText(PlayingLen ));
        if(!(data.Position >= 0))
            data.Position = 0;
        if(RadioMode) 
            $.getJSON('?GetRadio', ParseRadio);
        CurrentTime = data.Position / 1000; // sec
        $('#lTime').text(GetLenText(data.Position)+' /');
        var progress_vis = data.ProgressVisible ? 'visible': 'hidden';
        $('#progress').css('visibility', progress_vis);
        document.getElementById('progress').value = CurrentTime * 1000 / PlayingLen  * 100;
        $('#bPlay').attr('src', 'img/playa.png')
        if (data.Paused)
        {
            Paused = true;
            $('#bPause').attr('src', 'img/pausea.gif')
        }
        else
         {
            Paused = false;
            $('#bPause').attr('src', 'img/pausep.png')
         }
         if (data.RadioTitle.length > 0 && RadioMode) {
            var q = data.RadioTitle;                                                     
            FlagRadio = true;
            var txt = '<br>'+q;
            if( $('#lRadio').html() != txt)
            {
                $('#lRadio').html(txt);                        
                if(ShowRPcs && ViewPictures)   
                 ParseRadioPictures(q);
            }
        }
         else  
         {
             $('#lRadio').text("");
             FlagRadio = false;
         }
    }
}

function UpdateState()
{
     $.getJSON('?State', ParseState);
}

function VolPlus() {
   var x = $('#volume')[0].value;
   var n =   parseInt(x)+25;
   if(n > 100)
    n=100;
    $('#volume')[0].value = n;
    VolumeChange(n);
}
function VolSub() {
   var y = $('#volume')[0].value;
   var n = parseInt(y)-25;
   if(n < 0)
    n=0;
    $('#volume')[0].value = n;
    VolumeChange(n);
}

function OnRadio() {
    $('#lRadio').text('');    
    RadPcs.length = 0; 
    OldViewPictures = false;
    $.getJSON('?RadioMode', ParseAlbums);
}

