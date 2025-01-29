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
var PlayTimerId = null; // идентификатор таймера воспроизведения.
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
var hasHGlass = false;
var SaveName = ""
var FlagUpdate = false;
var Config = new Object();;
// Инициализация при загрузке
$.getJSON('?GetConfig',OnLoadConfig);
setTimeout(function() {$.getJSON('?GetAlbums', ParseAlbums)}, 200);
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
     elm = document.getElementById('config_body');	
     if (navigator.userAgent.indexOf('Firefox')!=-1) elm.style.boxShadow='none';  
     elm.style.webkitTransform = elm.style.msTransform =  elm.style.mozTransform =   elm.style.transform = 'scale('+coeff+')'; 
   }
   UpdateState();
   if(PlayTimerId == null)
        SetTimer();
   getJSON('?GetAlbums', ParseAlbums);
}

function OnPictureMode() {
    if (ViewPictures) {

        $('#ImgBlock').css('display', 'none');
        $('#PictMode').css('display', '');
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
        $('#PictMode').css('display', 'none');
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
                 $(val).css('background-color', 'rgb(184,184,184)');
            else 
                 $(val).css('background-color', 'rgb(104,104,104)');
            SaveName = $(val).text().split('.')[0];
        }
        else
            $(val).css('background-color', 'rgb(68,68,68)');
    });
}

function OnSelectPlaylist_(data)
{      
    Playlists = data.Playlists;
    $('#Playlists').empty();    
    if (Playlists != null && Playlists.length > 0) 
    {
        $.each(Playlists, function (ind, val) 
        { 
          var val_ = (ind == 0) ? 'Last Playlist' : val; 
          $('#Playlists').append('<tr><td  onclick="SelectPlaylist(' + ind + ')">' + val_ + '</td></tr>'); 
        } );     
    }
    SetPlaylistSelColor(false);
    $('#bOpen').attr('src', 'img/opena.png')
    $('#Albums').css('visibility', 'hidden');
    $('#Songs').css('display', 'none');
    $('#Playlists').css('display', '');    
    $('#SongsBlock').scrollTop(DirsListPos);
}

function MakeHourglass()
{   
   $('#Hourglass').css('visibility', 'visible');
     hasHGlass = true;
}

function RemoveHourglass()
{
    $('#Hourglass').css('visibility', 'hidden');
        hasHGlass = false;
}

function ReturnToSongs()
{
    $('#SongsBlock').scrollTop(0);
    $("#Input").css('display', 'none');
    $("#Filter").css('display', 'block');
    if (OldViewPictures)
        OnPictureMode();
    if (!AddMode && PlaylistIndex != -1) {
          $('#Albums').empty();
          $('#Songs').empty();
    }
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
      SaveName = "";
      PlaylistIndex = PlaylistIndexNew = -1;     
      $("#Search").val(""); 
      $('option').css('display', 'block');  
      $.getJSON('?GetPlaylists&ind=-1', OnSelectPlaylist_);
      ModePlaylists = true;
      OldViewPictures = ViewPictures;
      $("#Filter").css('display', 'none');
      if (ViewPictures)
          OnPictureMode();
    }
    else
    {
        DirsListPos =  $('#SongsBlock').scrollTop();
        $('#SongsBlock').scrollTop(0);
        if (PlaylistIndexNew == null)
            PlaylistIndexNew = 0;
        PlaylistIndex = PlaylistIndexNew;
        if (PlaylistIndex != -1) 
        {         
            SetPlaylistSelColor(true);
            MakeHourglass();
            if(AddMode)
                $.getJSON('?Get_Playlist&playlist=' + PlaylistIndex, ParseStd);
            else                                                        
                $.getJSON('?GetPlaylist&playlist=' + PlaylistIndex, ParseStd);
                
        }        
            $('#bOpen').attr('src', 'img/openp.png')
            ModePlaylists = false;
            $('#Albums').css('visibility', 'visible');
            $('#Songs').css('display', '');
            $('#Playlists').css('display', 'none');
            ReturnToSongs();       
    }     
}

function StatusClick()
{	
  if (ModePlaylists == true) 
  {
	PlaylistIndexNew = -1;
	OnSelectPlaylist(false);
  }
}

function ChangeImage()
{
    $('#Image').attr('src', '?GetImage&pl=' + PlaylistIndex + '&image=' + CurrentAlbum + '&time=' + (new Date()).getTime());
}

function OnViewAlbum()
{
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
    $('#SongsBlock').scrollTop(0);
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
    VolumeCommand(vol);     
}

function GetLenText(len) // Время в формате 0:00:00
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
        $.each(Songs, function (ind, val) { $('#Songs').append('<tr><td  onclick="SelectSong(' + ind + ',false)" ondblclick="PlayCommand2(' + ind + ')">' + val + '</td></tr>'); });
         if(Playing && CurrentAlbum == PlayingAlbum)
         {
             SelectSong(PlayingSong,true);           
         }
         else
         if(bFirst)
               SelectSong(SavedSongIndex,true);  
         else
            SelectSong(-1,true);
    }
    else
        CurrentSong = null;    
    $('#SongsBlock').scrollTop(0);	
}

function ParseAlbums(data)
{
    $('#bOpen').attr('src', 'img/openp.png')
    ModePlaylists = false;
    $('#Albums').css('visibility', 'visible');
    $('#Songs').css('display', '');
    $('#Playlists').css('display', 'none');
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
        $.each(Albums.Albums, function (ind, val) { $('#Albums').append('<option value=' + ind + ' title="'+ val +'">' + val + '</option>'); });
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
        $.each(Playlists, function (ind, val) { var val_=(ind==0)? 'Last Playlist': val;$('#Playlists').append('<tr><td  onclick="SelectPlaylist('+ind+')" ondblclick="OnSelectPlaylist()">'+val_+'</td></tr>'); });
      //  SelectPlaylist(PlaylistIndex);
    }
    //SelectSong(SavedSongIndex,true);
    if(FlagUpdate)    
    	document.location = '/';
	 else    
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
         $('#lTime').text(GetLenText(CurrentTime * 1000));
          current = new Date().getTime();
          if (current - timestamp > 2500) 
                 len = 0;
           timestamp = current;                      
     }          
     if(($('#lKhz').text() == '' || len /1000 - CurrentTime < 5 || $('#progress').css('visibility') == 'hidden'  || CurrentTime < 5) && Playing || Paused || hasHGlass)
        UpdateState();     
     if(RadioMode && $('#Image').width()==0 && Playing &&  ShowRPcs && RadPcs.length > 0 && CurrentAlbum == PlayingAlbum) 
        UpdateRadioPicture();
     if(RadioMode && Playing && Math.floor(CurrentTime) % 10 == 0)
     {       
        $.getJSON('?GetRadio', ParseRadio);
        if(Math.floor(CurrentTime) % PicturesInterval == 0 && CurrentAlbum == PlayingAlbum && ViewPictures && FlagRadio)
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
            $('#Image').attr('src',RadPcs[IndRadPcs]);
            IndRadPcs = IndRadPcs + 1;
        }  
        else
            ChangeImage();
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
   var tx = $('#lRadio').text().trim();
	if(tx == '')
	{
	    RadPcs.length = 0;
	    IndRadPcs = 0;  
	    ChangeImage();
		return;         
	}
   $.getJSON('?GetPictures', OnParseRadioPictures); 
}


function ParseRadio(data)
{
    var txt = data.RadioTitle;
    if($('#lRadio').text() != txt ) 
    {       
            $('#lRadio').text(txt);    
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
  UpdateRadioPicture();            
}

function PlayCommand()
{    
    if(CurrentAlbum == null || CurrentSongs == null || Playing || CurrentSongs.Length == 0 || ModePlaylists == true || hasHGlass)
        return;  
    PlayingSong = CurrentSong; 
    //$.each($('#Songs').children(), function (ind2, val) { $(val).css('background-color', 'rgb(68,68,68)'); });
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
  //  $.each($('#Songs').children(), function (ind2, val) { $(val).css('background-color', 'rgb(68,68,68)'); });
    if (Playing) 
    {
        StopCommand(ParseStopPlay);
    }
    else
        PlayCommand();
}

 function ParsePath(data)
 {
     var str = data.Path;   
     $('#lRadio').html(str);
 }


function SelectSong(ind, au)
{   
    CurrentSong = ind;
    $.each($('#Songs').children(), function (ind2, val) {
        if (ind == ind2)  {
            $(val).css('background-color', 'rgb(104,104,104)');
            if (!Playing) 
            {
                $.getJSON('?GetPath&album=' + CurrentAlbum + '&song=' + CurrentSong, ParsePath);
            }
        }
        else
            $(val).css('background-color', 'rgb(68,68,68)');
    });
   if(au)
    { 
        var h = $('#Songs tr').eq(0).height();
        $('#SongsBlock').scrollTop((ind-3)*h);
    }
}

function SelectPlaylist(ind)
{
    DirsListPos =  $('#SongsBlock').scrollTop();
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
  // if(!Playing) 
  //  $('#bPlay').attr('src', 'img/playa.png')
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
   $('#bOpen').attr('src', 'img/opena.png')
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
                $('#bRadio').attr('src', 'img/radioa.png');                                 
            else            
                $('#bRadio').prop('src', 'img/radiop.png')                                    
         }
    if(RadioMode && (Boolean(ShowRPcs) != Boolean(data.RadioPictures)))
    {                
        ShowRPcs = data.RadioPictures;
        if(ShowRPcs && ViewPictures)
            ParseRadioPictures();
        else
            UpdateRadioPicture();
    }
    if (!data.Playing) 
    {        
        Playing = false;
        $('#lArtist').text('');
        $('#lArtist').attr('title','');      
        $('#lAlbum').text('');
        $('#lAlbum').attr('title','');
        $('#lYear').text('');
        $('#lNum').text('');
        $('#lSong').text('');
        $('#lSong').attr('title','');
  			if(!hasHGlass)      
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
     //   var newsong = PlayingSong != data.Song;
        PlayingSong = data.Song;
        if(PlayingAlbum == CurrentAlbum) {
        	//     if(newsong)
                SelectSong(PlayingSong,true);
        }
        else {            
           if (new_alb)
            {
                OnViewAlbum();
                SelectSong(PlayingSong,true);
            }
            else
                SelectSong(-1,true);
        }
        $('#lArtist').text(Albums.Authors[data.Album]);
        $('#lArtist').attr('title', $('#lArtist').text());
        $('#lAlbum').text(Albums.AlbumsS[data.Album]);       
        $('#lAlbum').attr('title', $('#lAlbum').text());
        $('#lYear').text(Albums.Years[data.Album]);
        $('#lNum').text((data.Song+1).toString()+'.');
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
        $('#lTime').text(GetLenText(data.Position));         
        var progress_vis = data.ProgressVisible ? 'visible': 'hidden';
        $('#progress').css('visibility', progress_vis);        
        document.getElementById('progress').value = CurrentTime * 1000 / PlayingLen  * 100;
        $('#bPlay').attr('src', 'img/playa.png')
        if (data.Paused) 
        {
            Paused = true;
            $('#bPause').attr('src', 'img/pausea.png')
        }
        else
         {
            Paused = false;
            $('#bPause').attr('src', 'img/pausep.png')
         }         
         if (data.RadioTitle.length > 0 && RadioMode) {
            var txt = data.RadioTitle;
             if($('#lRadio').text() != txt) {
                $('#lRadio').text(txt);        
                if(ShowRPcs && ViewPictures)           
                    ParseRadioPictures();
             }
             FlagRadio = true;
         }
         else  {
             $('#lRadio').text("");
             FlagRadio = false;
         }
    }
}

function UpdateState()
{
     $.getJSON('?State', ParseState);    
}

//Plus
function PlusDown() {
    $('#bPlus').attr('src', 'img/plusa.png')
}
function PlusUp() {
    $('#bPlus').attr('src', 'img/plusp.png')
}
function PlusCommand() {
    if(hasHGlass)
        return;
    if(RadioMode)
    {     
      
        if(Playing)
				$.getJSON('?Favor', ParseStd);
			else {           
        if (!ViewPictures)
        		OnPictureMode()
        $('#reditok2').css('visibility','hidden');     
        $('#rsect').val(Albums.Albums[CurrentAlbum]);
        $('#rname').val('');        
        $('#rstat').val('');        
        $('#rmess').text('');
        $('#reditok').text("Add");
        $('#Image').css('display','none');
        $('#redit').css('display','block');
		  }        
        return;
    }
    if (ModePlaylists == true)
    {
        $("#PlaylistName").val(SaveName);
        $("#Input").css('display', 'block');
    }
    else
    {
       OnSelectPlaylist(true);
    }
}

function MinusDown() {	
    $('#bMinus').attr('src', 'img/minusa.png')
}
function MinusUp() {
    $('#bMinus').attr('src', 'img/minusp.png')
}

function ParseStation(data)
{
      $('#rstat').val(data.url);    
}	
	
function MinusCommand() {
    if(hasHGlass)
        return;
    if(RadioMode)
        {        
            $('#reditok2').css('visibility','visible');    
	      	if(Playing)
					StopCommand(ParseStd)            
      		if (!ViewPictures)
        			OnPictureMode()
            $('#rsect').val(Albums.Albums[CurrentAlbum]);
            $('#rname').val(CurrentSongs[CurrentSong]);        
            $('#rstat').val('');        
            $("#rmess").text('');
            $("#reditok").text("Delete");
            $("#Image").css('display','none');
            $("#redit").css('display','block');
            $.getJSON('?DeleteStationInfo&album='+ CurrentAlbum+'&song='+CurrentSong, ParseStation);
            return;
        }
    if (ModePlaylists == true) {
      if (PlaylistIndexNew > 0)
        {               
            var row = $("#Playlists").children().eq(PlaylistIndexNew);       
            if(row != undefined)
            {                                               
                var cell = row.children().first();
                var text = cell.text();
                var subtext = text.substring(0, 1).trim();                
                if (text[0] != '⊞' && text[0] != '⊟' && subtext.length > 0) {
                    var ok = confirm("Do you want to remove a playlist named  '" + cell.text() + "' ?");
                    if(ok == true)
                    {
                        $("#Input").css('display', 'none');
                        PlaylistIndexNew = -1;
                        $.getJSON('?DeletePlaylist&name='+text, OnSelectPlaylist_);                               
                    }
                }              
            }                           
        }
    }
    else {
     if(CurrentAlbum == null)
     return;
     $("#Search").val("");
     OldViewPictures = false;
    $.getJSON('?DeleteAlbum&album='+ CurrentAlbum, ParseAlbums);
    }    
}

function OnRadio() {
    $('#lRadio').text('');    
    RadPcs.length = 0;   
    OldViewPictures = false;
    MakeHourglass();
    //RadioMode = !RadioMode;
    $.getJSON('?RadioMode', ParseAlbums);
}

function ClearDown() {
    $('#bClear').attr('src', 'img/closea.png')
}
function ClearUp() {
    $('#bClear').attr('src', 'img/closep.png')
}
function ClearCommand() {
    $('#Search').val("");
    OnSearch();
}

function OkDown() {
    $('#bOk').attr('src', 'img/oka.png')
}
function OkUp() {
    $('#bOk').attr('src', 'img/okp.png')
}
function OkCommand() {
    var str = $('#PlaylistName').val();
    $("#Input").css('display', 'none');
    if (str.length > 0) {        
        PlaylistIndexNew = -1;
        $.getJSON('?SavePlaylist&name='+str, OnSelectPlaylist_);        
    }
}


function OnSearch()
{
    var text = $('#Search').attr('value');  
     if ( text.length == 0)
     {
        $('option').css('display', 'block');  
        return;
    }
   var l = Albums.Albums.length;
   var q = text.toLowerCase();
   var found = null;
   for (s = 0; s < l; s++) 
   {   
    var el = Albums.Albums[s];   
    var val = el.toLowerCase();   
    var ok = val.indexOf(q) > -1;
    if (ok)
     {
        $('option').eq(s).css('display', 'block');
        if (found == null)
            found = $('option').eq(s);
    }
    else
        $('option').eq(s).css('display', 'none');   
   }
   if (found != null)
    {
        found.prop('selected', true); 
        ChangeAlbum(found);     
    }
}



function OnLoadStatus(data)
{
    Config = data;
    $('#stat_root').text((Config.stat_root == 1) ? "yes" : "no");
    $('#stat_cores').text(Config.stat_cpus.toString());
    $('#stat_prio').text(Config.stat_prio.toString());
    $('#stat_nice').text(Config.stat_nice.toString());
    $('#stat_16bit').text((Config.stat_16bit == 1) ? "yes" : "no");
    $('#stat_24bit').text((Config.stat_24bit == 1) ? "yes" : "no");
    $('#stat_32bit').text((Config.stat_32bit == 1) ? "yes" : "no");
    var is_playing = Config.stat_playing == 1;
    $('#stat_play').text(is_playing ? "yes" : "no");     
    if(is_playing)
    {      
      $('#status tr.playing').show();
      $('#stat_play_file').text(Config.stat_file);
      $('#stat_period_size').text(Config.stat_period_size+" frames, "+ Config.stat_period_time+" µs");      
      $('#stat_buffer_size').text(Config.stat_buffer_size + " frames, "+ Config.stat_period_time*Config.stat_buffer_size/Config.stat_period_size  + " µs");  
    }
    else
    {
      $('#status tr.playing').hide();
      $('#stat_play_file').text("");
      $('#stat_period_size').text("");
      $('#stat_buffer_size').text("");
    }

}

function init_config(){
    var tabs = $('#tabs');
    $('.tabs-content > div', tabs).each(function(i){
        if ( i != 0 ) $(this).hide(0);
        else $(this).show();
    });
    tabs.on('click', '.tabs a', function(e){     
        e.preventDefault();
        var tabId = $(this).attr('href');
        $('.tabs a',tabs).removeClass();
        $(this).addClass('active');
        $(tabId).find('a').first().addClass('active');
        $('.tabs-content > div', tabs).hide(0);
        $('.tabs-content > div > div', tabs).hide(0);
        if(tabId.toString() == "#status")
        $.getJSON('?GetConfig', OnLoadStatus);
        $(tabId).parent().show();     
        $(tabId).show();        
        $(tabId).find('div').first().show(); 

    });
    $.getJSON('?GetConfig', OnLoadConfig);
}

function ShowConfig()
{
     $('#all').css('display','none');
     $('#config_body').css('display','block');
     init_config();
}

function HideConfig()
{
   document.location = '/';
   //  $('#all').css('display','block');
   //  $('#config_body').css('display','none');     
}

function OnLoadConfig(data)
{
    Config = data;
    $('#covers').val(Config.covers);        
    if(Config.di_mode == 1)
        $('#radio_di').prop('checked','checked');
    else
    if(Config.di_mode == 2)    
        $('#radio_fm').prop('checked','checked');
    else
        $('#radio_st').prop('checked','checked');
    $('#check_gapless').prop('checked',Config.gapless_mode ? 'checked' : '');
    $('#check_preload').prop('checked', Config.preload_mode ? 'checked' : '');
    $('#pframes').val(Config.pframes.toString());
    $('#bframes').val(Config.bframes.toString());
    $('#pmks').val(Config.pmks.toString());
    $('#bmks').val(Config.bmks.toString());     
    $('#check_volume').prop('checked',Config.volume_enabled ? 'checked' : '');
    $('#check_auto').prop('checked',Config.auto_mode ? 'checked' : '');
    $('#check_cue').prop('checked',Config.cue_mode ? 'checked' : '');
    $('#check_tags').prop('checked',Config.tags_mode ? 'checked' : '');
    $('#check_16bit').prop('checked',Config.mode_16bit ? 'checked' : '');
    $('#check_24bit').prop('checked',Config.mode_24bit ? 'checked' : '');
    $('#check_memory').prop('checked', Config.lock_memory ? 'checked' : '');
    $('#priority').val(Config.priority.toString());   
    $('#nice').val(Config.nice.toString());   
    if(Config.affinity_mode == 1) 
        $('#cores1').prop('checked','checked');
    else
    if(Config.affinity_mode == 2) 
        $('#cores2').prop('checked','checked');
    else
        $('#cores0').prop('checked','checked');
    $('#stat_root').text((Config.stat_root == 1) ? "yes" : "no");
    $('#stat_cores').text(Config.stat_cpus.toString());
    $('#stat_prio').text(Config.stat_prio.toString());
    $('#stat_nice').text(Config.stat_nice.toString());
    $('#stat_16bit').text((Config.stat_16bit) ? "yes" : "no");
    $('#td16').css('visibility',(Config.stat_16bit && (Config.stat_24bit || Config.stat_32bit)) ? 'visible' : 'hidden');
    $('#td24').css('visibility',(Config.stat_24bit && Config.stat_32bit) ? 'visible' : 'hidden');
    $('#stat_24bit').text((Config.stat_24bit) ? "yes" : "no");
    $('#stat_32bit').text((Config.stat_32bit) ? "yes" : "no");
    $('#stat_dsd').text("no");
    if((Config.stat_dsd32be == 1))
            $('#stat_dsd').text("DSD_U32_BE");
    if((Config.stat_dsd32le == 1))
            $('#stat_dsd').text("DSD_U32_LE") ;     
    var can_native = Config.stat_dsd32le ==1 || Config.stat_dsd32be==1;
    $('#dsd_n').prop("disabled",can_native ? false : true);
    $('#sdm_n').prop("disabled",can_native ? false : true);
    var is_playing = Config.stat_playing == 1;
    $('#stat_play').text(is_playing ? "yes" : "no");
    if(is_playing)
    {      
      $('#status tr.playing').show();
      $('#stat_play_file').text(Config.stat_file);
      $('#stat_period_size').text(Config.stat_period_size);
      $('#stat_period_time').text(Config.stat_period_time);
      $('#stat_buffer_size').text(Config.stat_buffer_size);
    }
    else
    {
      $('#status tr.playing').hide();
      $('#stat_play_file').text("");
      $('#stat_period_size').text("");
      $('#stat_period_time').text("");
      $('#stat_buffer_size').text("");
    }
    $('#CardNum').text("");    
    $('#asound').text("");    
    if(Config.asound != undefined && Config.asound != null)  
        Config.asound.forEach(function(element) {
        $('#asound').append(element.toString());    
        $('#asound').append("<br>");
        }, Config.asound);
    $('#cards').text("");
    if(Config.cards != undefined && Config.cards != null)  
        Config.cards.forEach(function(element, i) {
        $('#cards').append(element.toString());    
        $('#cards').append("<br>");
        if(i % 2)
            $('#cards').append("<br>");
        }, Config.cards);
    //DSP
    if(Config.multi_mode) 
    $('#check_multi').prop('checked','checked');
    else
    $('#check_multi').prop('checked','');
    if(Config.swap_mode) 
    $('#check_swap').prop('checked','checked');
    else
    $('#check_swap').prop('checked','');
    if(Config.phase_mode) 
    $('#check_phase').prop('checked','checked');
    else
    $('#check_phase').prop('checked','');
    $('#res_bf44').val(Config.res_bf44).change();
    $('#res44').val(Config.res44).change();   
    $('#res48').val(Config.res48).change();   
    $('#res88').val(Config.res88).change();   
    $('#res96').val(Config.res96).change();   
    $('#res176').val(Config.res176).change();   
    $('#res192').val(Config.res192).change();   
    $('#res352').val(Config.res352).change();   
    $('#res384').val(Config.res384).change();   
    $('#soxr_phase').val(Config.soxr_phase).change();   
    $('#soxr_filter').prop('checked',Config.soxr_filter ? 'checked' : '');
    $('#soxr_quality').prop('checked', Config.soxr_quality ? 'checked' : '');
    //DSD
    $('#dsd_mode').val(Config.dop_mode).change();   
    $('#dsd_pcm_freq').val(Config.pcm_freq).change();   
    $('#dsd_mode_limit').val(Config.dsd_limit).change();   
    $('#dsd_to_pcm_mode').val(Config.dsd_pcm).change();  
    $('#dsd_pcm_volume').val(Config.dsd_vol).change();  
    $('#dsd_area').val(Config.dsd_area).change();    
    $('#dsd_pcm_multi').prop('checked', Config.pcm_mult ? 'checked' : '');
    $('#sacd_full').prop('checked', Config.sacd_full ? 'checked' : '');
    $('#dvda_area').val(Config.dvda_area).change();    
    $('#dvda_nomix').prop('checked', Config.dvda_nomix ? 'checked' : '');    
    //Radio
    $('#radio_pict').prop('checked', Config.radio_pict ? 'checked' : '');
    $('#radio_proxy').prop('checked', Config.radio_proxy ? 'checked' : '');
    $('#radio_ua').prop('checked', Config.radio_ua ? 'checked' : '');
    $('#radio_user').val(Config.radio_user.toString());    
    $('#radio_proxy2').val(Config.radio_proxy2.toString());
    $('#std_buffer').val(Config.std_buffer.toString());
    $('#silence').val(Config.si_time.toString());
    if(Config.mmap == 1)    
        $('#mmap').prop('checked','checked');
    else
        $('#rw').prop('checked','checked');
    $('#enable_dsd').prop('checked',Config.enable_dsd ? 'checked' : '');
    $('#dsd_filter').val(Config.filter).change();
    $('#dsd_output').val(Config.output).change();
    $('#dsd_rate').val(Config.rate).change();
    $('#dsd_level').val(Config.level).change();
    $('#dsd_multithread').prop('checked',Config.multithread ? 'checked' : '');
    $('#root').val(Config.root_folder);     

    if(Config.exists_conv == true) 
        $('#convtab').css('display','block');
     else 
        $('#convtab').css('display','none');
    $('#filters').html(Config.conv_list);    
    $('#filtdesc').html(Config.conv_desc);    
    $('#FilterNum').val(Config.conv_filter).change();             
    $('#db').val(Config.conv_db/10.0).change();             
    $('#conv_en').prop('checked', Config.use_conv ? 'checked' : '');
    $('#hwvolume').prop('checked', Config.hw_volume? 'checked' : '');
    $('#hwlist').html(''); 
    var nn = 0;
      if(Config.hw_list != undefined && Config.hw_list != null)  
        Config.hw_list.forEach(function(element, i) {
      $('#hwlist').append(new Option(element, nn++));    
        }, Config.hw_list);
        if (nn) {
        	           $('#hwvolume').css('display','inline-block');
  						  $('#lhw').css('display','inline');
  						  $('#hwlist').css('display','inline');			  
        }
        else {
                    $('#hwvolume').css('display','none');
  						  $('#lhw').css('display','none');
  						  $('#hwlist').css('display','none');
        }
      if(nn> Config.hw_index) 			
           $('#hwlist').val(Config.hw_index.toString());
      $('#att').val(Config.att.toString());
      $('#win1251').prop('checked',Config.win1251 ? 'checked' : '');
      $('#num_pics').val(Config.num_pics.toString());
      $('#catdate2').text(Config.catdate.toString());
      $('#api_cx').val(Config.api_cx.toString());
      $('#api_key').val(Config.api_key.toString());     
      $('#radio_q').prop('checked',Config.api_flag ? 'checked' : '');
      $('#radio_qv').val(Config.api_add.toString());
      $('#radio_auto').prop('checked',Config.radio_auto ? 'checked' : '');
      $('#a_color').val(Config.a_color.toString());
      $('#t_color').val(Config.t_color.toString());  
      $('#ai_color').val(Config.ai_color.toString());
      $('#ti_color').val(Config.ti_color.toString());  
      $('#i_color').val(Config.i_color.toString());
      $('#yi_color').val(Config.yi_color.toString());   
      $('#ar_color').val(Config.ar_color.toString()); 
      SetColors();
      $.get('/date.html', function(data) {$('#catdate1').text(data);}); 
 }

function ExitApp()
{
  document.location = '/stop';
}

function StartPage2()
{    
 // document.location = '/';
  StopCommand(ParseStd); 
  $('#CardNum').val('').change(); 
  ShowConfig();
}

function StartPage()
{
    setTimeout(StartPage2, 2000);
}


function SaveConfig()
{   
    if($('#CardNum').val() != '')
    {        
        SelectCard();   
        return;    
    }

    var arr = new Object;
    arr['covers'] =  $('#covers').val();      
    arr['gapless_mode'] = $('#check_gapless').is(':checked') ? true : false;
    arr['preload_mode'] = $('#check_preload').is(':checked') ? true : false;
    arr['di_mode'] =  $('#radio_di').is(':checked') ? 1 : ($('#radio_fm').is(':checked') ? 2 : 0);
    arr['pframes'] = parseInt($('#pframes').val());  
    arr['bframes'] = parseInt($('#bframes').val());  
    arr['pmks'] = parseInt($('#pmks').val());  
    arr['bmks'] = parseInt($('#bmks').val());  
    arr['mode_16bit'] = $('#check_16bit').is(':checked') ? true : false;
    arr['mode_24bit'] = $('#check_24bit').is(':checked') ? true : false;
    //DSD
    arr['dop_mode'] = parseInt($('#dsd_mode').val());  
    arr['pcm_freq'] = parseInt($('#dsd_pcm_freq').val());  
    arr['dsd_limit'] = parseInt($('#dsd_mode_limit').val());  
    arr['dsd_pcm'] = parseInt($('#dsd_to_pcm_mode').val());  
    arr['dsd_vol'] = parseInt($('#dsd_pcm_volume').val()); 
    arr['dsd_area'] = parseInt($('#dsd_area').val()); 
    arr['sacd_full'] = $('#sacd_full').is(':checked') ? true : false;   
    arr['dvda_area'] = parseInt($('#dvda_area').val()); 
    arr['dvda_nomix'] = $('#dvda_nomix').is(':checked') ? true : false;
    arr['pcm_mult'] = $('#dsd_pcm_multi').is(':checked') ? true : false;
    arr['volume_enabled'] = $('#check_volume').is(':checked') ? true : false;
    arr['auto_mode'] = $('#check_auto').is(':checked') ? true : false;
    arr['cue_mode'] = $('#check_cue').is(':checked') ? true : false;  
    arr['tags_mode'] = $('#check_tags').is(':checked') ? true : false;
    arr['lock_memory'] = $('#check_memory').is(':checked') ? true : false;
    arr['priority'] = parseInt($('#priority').val());
    arr['nice'] =  parseInt($('#nice').val());
    if($('#cores1').is(':checked'))
        arr['affinity_mode'] = 1;
    else    
        arr['affinity_mode'] = $('#cores2').is(':checked') ? 2 : 0;
    arr['multi_mode'] = $('#check_multi').is(':checked') ? true : false;    
    arr['swap_mode'] = $('#check_swap').is(':checked') ? true : false;    
    arr['phase_mode'] = $('#check_phase').is(':checked') ? true : false;    
    arr['res_bf44'] =parseInt($('#res_bf44').val());   
    arr['res44'] =parseInt($('#res44').val());   
    arr['res48'] =parseInt($('#res48').val());   
    arr['res88'] =parseInt($('#res88').val());   
    arr['res96'] =parseInt($('#res96').val());   
    arr['res176'] =parseInt($('#res176').val());   
    arr['res192'] =parseInt($('#res192').val());   
    arr['res352'] =parseInt($('#res352').val());   
    arr['res384'] =parseInt($('#res384').val());   
    arr['soxr_phase'] =parseInt($('#soxr_phase').val());  
    arr['soxr_filter'] = $('#soxr_filter').is(':checked') ?  true : false;    
    arr['soxr_quality'] =  $('#soxr_quality').is(':checked') ? true : false;    
    //Radio         
    arr['radio_pict'] = $('#radio_pict').is(':checked') ? true : false;   
    arr['radio_proxy'] = $('#radio_proxy').is(':checked') ? true : false;   
    arr['radio_proxy2'] = $('#radio_proxy2').val(); 
    arr['radio_ua'] =  $('#radio_ua').is(':checked') ? true : false;   
    arr['radio_user'] = $('#radio_user').val();   
    arr['std_buffer'] = parseInt($('#std_buffer').val());  
    arr['si_time'] = parseInt($('#silence').val());  
    arr['mmap'] =  $('#mmap').is(':checked') ? 1 : 0;
    arr['enable_dsd'] =  $('#enable_dsd').is(':checked') ? 1 : 0;
    arr['filter'] =parseInt($('#dsd_filter').val());   
    arr['output'] =parseInt($('#dsd_output').val());   
    arr['rate'] =parseInt($('#dsd_rate').val());   
    arr['level'] =parseInt($('#dsd_level').val());   
    arr['multithread'] = $('#dsd_multithread').is(':checked') ? 1 : 0;   
    var rf =  $('#root').val();   
    if(rf.length > 0)
    {
     if(rf.charAt(0) == '/')
       rf = rf.substr(1);
     if(rf.length > 0)
        if(rf.charAt(rf.length-1) == '/')
          rf = rf.slice(0, -1);
    }
    arr['root_folder'] = rf; 
    arr['use_conv'] = $('#conv_en').is(':checked') ? true : false;      
    arr['conv_filter'] =parseInt($('#FilterNum').val());
    arr['conv_db'] = document.getElementById('db').value * 10;   
    arr['hw_index'] =parseInt($('#hwlist').val());   
    arr['hw_volume'] = $('#hwvolume').is(':checked') ? true : false;  
    arr['att'] = parseInt($('#att').val());
    arr['win1251'] =  $('#win1251').is(':checked') ? 1 : 0;
    arr['num_pics'] = parseInt($('#num_pics').val());   
    arr['api_cx'] = $('#api_cx').val();
    arr['api_key'] = $('#api_key').val();
    arr['api_flag'] = $('#radio_q').is(':checked') ? 1 : 0;
    arr['api_add'] = $('#radio_qv').val();
    arr['radio_auto'] = $('#radio_auto').is(':checked') ? 1 : 0; 
    arr['a_color'] = $('#a_color').val();
    arr['t_color'] = $('#t_color').val();
    arr['i_color'] = $('#i_color').val();
    arr['ai_color'] = $('#ai_color').val();
    arr['ti_color'] = $('#ti_color').val();
    arr['yi_color'] = $('#yi_color').val();
    arr['ar_color'] = $('#ar_color').val();
    SetColors();
    $.ajax({
            url:'SetConfig',
            type:'POST',
             data: JSON.stringify(arr),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json'            
            }
        );        
    $('#config_label').text('Settings saved');
    setTimeout(function() {$('#config_label').text('');$.getJSON('?GetConfig', OnLoadConfig);UpdateState();}, 2000);
}

function SetDown()
{
   $('#bSet').attr('src', 'img/settingsa.png')
}

function SetUp()
{
   $('#bSet').attr('src', 'img/settingsp.png')
}

function SelectCard()
{
   if(Config.stat_root != 1)
    {
        alert("Root User required!");
        $('#CardNum').val('').change(); 
        return;
    }
    //StopTimer();
    var num =  $('#CardNum').val();
    $.getJSON('?SelectCard&card=' + num, StartPage);    
}

function redit_ok()
{    
  var add = $('#reditok').text() == 'Add';
  if(add)
  {
    var url = $('#rstat').val();
    var name = $('#rname').val();
    var folder = $('#rsect').val();
    if(url.indexOf("http://") == -1 &&  url.indexOf("https://") == -1)
    {
        $("#rmess").text("Please enter a network address (http://... or https://...)");
    }
    else if(name == "")
    {
        $("#rmess").text("Please enter a station name");
    }
    else if(folder == "")
    {
        $("#rmess").text("Please enter a section name");
    }
    else
    {
        var arr = new Object;
        arr['url'] = url;
        arr['name'] = name;
	    arr['folder'] = folder;        
        $.ajax({
            url:'?AddStation',
            type:'POST',
            data: JSON.stringify(arr),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            success: ParseAlbums             
            }
        );        
        redit_cl();
        MakeHourglass();
    }
  }
  else
  {
      $.getJSON('?DeleteStation&album='+ CurrentAlbum+'&song='+CurrentSong, ParseAlbums);
      redit_cl();
      MakeHourglass();
  }
}

function redit_ok2()
{
    $('#reditok').text('Add');
    redit_ok();
}

function redit_cl()
{
    $('#redit').css('display','none');
    $('#Image').css('display','inline');
}

function AttChange(pos)
{
        document.getElementById('dbtx').innerText = pos;
}

function PostUpdate()
{
	FlagUpdate = true;
	OnRadio();
}

function UpdateRadio(repl)
{
	      if(RadioMode)
	         $.get('?RadioMode', null);
	       $('#radwait').css('visibility','visible');
       $.get(repl ? '?Update1':'?Update0', PostUpdate);            
}

function SetColors()
{
	$('#Albums').css('color',$('#a_color').val());
	$('#Songs').css('color',$('#t_color').val());
	$('#lAlbum').css('color',$('#ai_color').val());
	$('#lNum').css('color',$('#ti_color').val());
	$('#lSong').css('color',$('#ti_color').val());
	$('.data').css('color',$('#i_color').val());
	$('#lRadio').css('color',$('#i_color').val());
	$('#lYear').css('color',$('#yi_color').val());
	$('#lArtist').css('color',$('#ar_color').val());
}

