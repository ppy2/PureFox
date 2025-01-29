var coeff = 1.0;   // Коэффициент масштабирования веб-интерфейса
var CurrentPlaylist = null; //Выбранный плейлист
var CurrentSong = null; // Выбранный трек, индекс
var PlayingSong = null; // Воспроизводимый трек, индекс
var Albums = new Object(); // Список альбомов плейлиста  Структура: Albums(полное описание) AlbumsS (только название) Authors Years Songs<Songs,SongsLen> Playlists
var CurrentAlbum = null; //Выбранный альбом, числовой индекс
var PlayingAlbum = -1; //Воспроизводимый альбом, числовой индекс
var Volume = null;  // Громкость
var Position = null;  // Позиция воспроизведения
var Playing = false; // Состояние воспроизведения
var CurrentSongs = null; // Список песен выбранного альбома
var CurrentTime = null; // Текущее время воспроизведения в секундах
var PlayTimerId = null; // идентификатор таймера воспроизведения
var Playlists = null;  // Список плейлистов
var Drives = null;  // Список плейлистов
var PlaylistIndex = 0; // Последний выбранный плейлист
var PlaylistIndexNew = 0; // Выбранный в списке плейлист
var Paused = false; // Состояние паузы
var ModePlaylists = false; // Выводится список плейлистов
var ChangePosition = false; // Обрабатывется позиционирование в треке
var ViewPictures = true; //  Режим отображения картинок
var PlayingLen = null; // Длительность воспроизводимого трека
var PlayingName = null;  // Воспроизводимый трек, название
var PicturesInterval = 20;  // Интервал обновления картинок, сек.
var IsSafari = navigator.userAgent.indexOf('Safari') !== -1 && navigator.userAgent.indexOf('Chrome') == -1 && navigator.userAgent.indexOf('Android') == -1;
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
//var DirsListPos = 0;
var openfolder = 'img/_folder_open_24.png';
var loadfolder = 'img/_eject_24.png';
var hasHGlass = false;
var AutoPlay = false;
var AddFolder = false;
var curlevel = 0;
var rootfolder = '';
var root = false;
var img;
var phone = /iPhone|iPod|Android/.test(navigator.userAgent);
var iOS = /iPhone|iPod|iPad/.test(navigator.userAgent);
var arr;
var leveltop = [0,0,0,0,0,0,0,0,0,0,0];
var NeedAlbums = false;
var FlagUpdate = false;

// Инициализация при загрузке
$.getJSON('?GetAlbums', ParseAlbums);
window.onblur = OnBlur;
window.onfocus = OnFocus;
SetTimer();

function rgb2hex(red, green, blue) {
    var rgb = blue | (green << 8) | (red << 16);
    return '#' + (0x1000000 + rgb).toString(16).slice(1)
}
function curColor(proc) {
    var r = Math.round(34+ proc * ( 255-34));
    var g = Math.round(139 - proc * 139);
    var b = Math.round(34 - proc * 34);;
    return rgb2hex(r,g,b);
}

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

/* ---------------------------- */
var hammertime;
function OnPicture(){
//    console.log('onpicture');
  var startScale = 1;
  var fixHammerjsDeltaIssue = undefined;
  var pinchStart = { x: undefined, y: undefined }
  var lastEvent = undefined;

  if ($('#Image').attr('data-width') == -1) return;

  if (iOS) {
    var wrap = document.getElementById('picturewrap');
    wrap.innerHTML = '';
    var image = document.getElementById('Image');
    if (image.detachEvent) {
	image.detachEvent('onclick', OnPicture)
    } else {
        image.removeEventListener('click',OnPicture);
    }
    wrap.appendChild(image);
    $('#Image').css('width',($('#Image').attr('data-width')+'px'));
    $('#Image').css('height',($('#Image').attr('data-height')+'px'));
    image.id = 'fullimg';
    $('#Image2').show();
  } else {
//    $('#fullimg').css('backgroundImage','url('+img.src+')'); //<--  #Image
    $('#fullimg').attr('src',img.src); //<--  #Image
      $('#fullimg').attr('data-width',img.width);
      $('#fullimg').attr('data-height',img.height);
    $('#fullimg').css('width',img.width);
    $('#fullimg').css('height',img.height);
  }

    var originalSize = {
	width:0,
	height:0
    };
    var current = {
        x: 0,
        y: 0,
        z: 1,
        zooming: false,
        width: 0,
        height: 0,
    }

    var last = {
        x: 0,
        y: 0,
        z: 0
    }

    function getRelativePosition(element, point, originalSize, scale) {
        var domCoords = getCoords(element);

        var elementX = point.x - domCoords.x;
        var elementY = point.y - domCoords.y;

        var relativeX = elementX / (originalSize.width * scale / 2) - 1;
        var relativeY = elementY / (originalSize.height * scale / 2) - 1;
        return { x: relativeX, y: relativeY }
    }

    function getCoords(elem) { // crossbrowser version
        var box = elem.getBoundingClientRect();

        var body = document.body;
        var docEl = document.documentElement;

        var scrollTop = window.pageYOffset || docEl.scrollTop || body.scrollTop;
        var scrollLeft = window.pageXOffset || docEl.scrollLeft || body.scrollLeft;

        var clientTop = docEl.clientTop || body.clientTop || 0;
        var clientLeft = docEl.clientLeft || body.clientLeft || 0;

        var top  = box.top +  scrollTop - clientTop;
        var left = box.left + scrollLeft - clientLeft;

        return { x: Math.round(left), y: Math.round(top) };
    }

    function scaleFrom(zoomOrigin, currentScale, newScale) {
        var currentShift = getCoordinateShiftDueToScale(originalSize, currentScale);
        var newShift = getCoordinateShiftDueToScale(originalSize, newScale)

        var zoomDistance = newScale - currentScale
        var shift = {
    	x: currentShift.x - newShift.x,
    	y: currentShift.y - newShift.y,
        }

        var output = {
            x: zoomOrigin.x * shift.x,
            y: zoomOrigin.y * shift.y,
            z: zoomDistance
        }
        return output
    }

    function getCoordinateShiftDueToScale(size, scale){
	var newWidth = scale * size.width;
        var newHeight = scale * size.height;
	var dx = (newWidth - size.width) / 2
	var dy = (newHeight - size.height) / 2
	return {
	    x: dx,
	    y: dy
	}
    }

    function update() {
        current.height = Math.round(originalSize.height * current.z);
        current.width = Math.round(originalSize.width * current.z);
	var minx = Math.round((current.width - originalSize.width)/2);
	var maxx = window.innerWidth - originalSize.width - (current.width - originalSize.width)/2;
	if (minx > maxx) {
	    var c = maxx;
	    maxx = minx;
	    minx = c;
	}
	if (current.x < minx) current.x = minx;
	if (current.x > maxx) current.x = maxx;

	var miny = Math.round((current.height - originalSize.height) / 2);
	var maxy = window.innerHeight - originalSize.height  - (current.height - originalSize.height)/2;
	if (miny > maxy) {
	    var c = maxy;
	    maxy = miny;
	    miny = c;
	}
	if (current.y < miny) current.y = miny;
	if (current.y > maxy) current.y = maxy;
        element.style.transform = "translate3d(" + current.x + "px, " + current.y + "px, 0) scale(" + current.z + ")";
    }

    function Init2(){
	originalSize = {
	    width: $('#fullimg').attr('data-width'),
	    height: $('#fullimg').attr('data-height')
	}
        current = {
         x: 0,
         y: 0,
         z: 1,
         zooming: false,
         width: originalSize.width * 1,
         height: originalSize.height * 1,
        }
        last = {
         x: 0,
         y: 0,
         z: 0
        }

        var ratio = window.innerWidth / window.innerHeight;
	var imgratio = originalSize.width / originalSize.height;
	if (ratio >imgratio) {
          current.height = window.innerHeight;
          current.width = Math.round(imgratio * window.innerHeight);

    	  current.x = Math.round(window.innerWidth - originalSize.width)/2;
          current.y = 0;
          current.z = window.innerHeight / originalSize.height;
        } else {
          current.width = window.innerWidth;
          current.height = Math.round(imgratio * window.innerWidth);
          current.x = 0;
          current.y = Math.round(window.innerHeight - originalSize.height)/2;
          current.z = window.innerWidth / originalSize.width;
        }
        startScale = current.z;
        last.x = current.x;
        last.y = current.y;
        last.z = current.z;
	update();
    }

    var element = document.getElementById('fullimg');
    hammertime = new Hammer(element, {});
    Init2();

    hammertime.get('pinch').set({ enable: true });
    hammertime.get('pan').set({ threshold: 0 });


    hammertime.on('doubletap', function(e) {
        var scaleFactor = 1;
        if (current.zooming === false) {
    	    $('#picturebox').addClass('zoomed');
            current.zooming = true;
        } else {
    	    $('#picturebox').removeClass('zoomed');
            current.zooming = false;
            scaleFactor = startScale;
        }

        element.style.transition = "0.3s";
        setTimeout(function() {
            element.style.transition = "none";
        }, 300)

        var zoomOrigin = getRelativePosition(element, { x: e.center.x, y: e.center.y }, originalSize, current.z);
        var d = scaleFrom(zoomOrigin, current.z, scaleFactor)
        current.x += d.x;
        current.y += d.y;
        current.z += d.z;

        last.x = current.x;
        last.y = current.y;
        last.z = current.z;

        update();
    });

    hammertime.on('pan', function(e) {
        if (lastEvent !== 'pan') {
            fixHammerjsDeltaIssue = {
                x: e.deltaX,
                y: e.deltaY
            }
        }

        current.x = last.x + e.deltaX - fixHammerjsDeltaIssue.x;
        current.y = last.y + e.deltaY - fixHammerjsDeltaIssue.y;
        lastEvent = 'pan';
        update();
    });

    hammertime.on('pinch', function(e) {
        var d = scaleFrom(pinchZoomOrigin, last.z, last.z * e.scale)
        current.x = d.x + last.x + e.deltaX;
        current.y = d.y + last.y + e.deltaY;
        current.z = d.z + last.z;
        lastEvent = 'pinch';
        update();
    });

    var pinchZoomOrigin = undefined;
    hammertime.on('pinchstart', function(e) {
        pinchStart.x = e.center.x;
        pinchStart.y = e.center.y;
        pinchZoomOrigin = getRelativePosition(element, { x: pinchStart.x, y: pinchStart.y }, originalSize, current.z);
        lastEvent = 'pinchstart';
    });

    hammertime.on('panend', function(e) {
        last.x = current.x;
        last.y = current.y;
        lastEvent = 'panend';
    });

    hammertime.on('pinchend', function(e) {
        last.x = current.x;
        last.y = current.y;
        last.z = current.z;
        lastEvent = 'pinchend';
    });

    document.querySelector('.update').addEventListener('click', function(){
	img = new Image();
        if (Playing && RadioMode && ShowRPcs && RadPcs.length > 0 && CurrentAlbum == PlayingAlbum)
        {
    	  img = new Image();
    	  img.src = RadPcs[IndRadPcs];
          IndRadPcs = IndRadPcs + 1;
          if (IndRadPcs >= RadPcs.length)
             IndRadPcs = 0;
	} else {
	  var imgstr = '?GetImage&pl=' + PlaylistIndex + '&image=' + CurrentAlbum + '&time=' + (new Date()).getTime();
	  img.src = imgstr;
        }
        img.onload = function() {
	    if (!iOS) {
              $('#Image').attr('src',img.src);
	         $('#Image').attr('data-width',img.width);
                 $('#Image').attr('data-height',img.height);
            }
            $('#fullimg').attr('src',img.src);
                $('#fullimg').attr('data-width',img.width);
                $('#fullimg').attr('data-height',img.height);
            $('#fullimg').css('width',img.width);
            $('#fullimg').css('height',img.height);
	    Init2();
        }


    });
    
    myPict.show();
}

function PlistShow()
{
    mySaveplist.show();
}

function OnFocus()
{
//   console.log('OnFocus');
   if (coeff!=1.0)
   {
     var elm = document.getElementById('all');
     if (navigator.userAgent.indexOf('Firefox')!=-1) elm.style.boxShadow='none';
     elm.style.webkitTransform = elm.style.msTransform =  elm.style.mozTransform =   elm.style.transform = 'scale('+coeff+')';
   }
   UpdateState();
   if (PlayTimerId == null)
        SetTimer();
   $.getJSON('?GetAlbums', ParseAlbums);
}

function fillFolders(data)
{
//    console.log('-> fillFolders');
    arr = [];
    Playlists = data.Playlists;
    Drives = data.Drives;
    var j=0;
    $('#Folders').empty();
    $('#Playlists').empty();
    if (Playlists != null && Playlists.length > 0)
    {
	$('#bread').empty();
	 var len = Drives.length;
	 var k = -1;
	 var path = '';
	 var lev = -1;
	 for (i=0;i<len;i++) {
            if (Drives[i].expanded == 1) { 
        	k = i;
        	path = Drives[i].path;
        	lev = Drives[i].level;
            }
         }
         if (root) {
            root = false;
            if(rootfolder == Drives[len-1].name) 
            	SelectPlaylist(len-1);
         }
         var ll = lev;
         var kk = k + 1;
         if (k > -1) { 
           var name = path.split('/').pop();
           arr.push({"num":k,"name":name,"position":0});
           j++;
         }
         while (lev > 0) {
           lev--;
           for (i=k; i>0;i--) {
            if ( lev == Drives[i].level) {
        	path = Drives[i].path;
        	k = i;
        	break;
            }
           }
           name = path.split('/').pop();
           arr.push({"num":k,"name":name,"position":0});
           j++;
         }
         if (rootfolder == '')
            arr.push({"num":-2,"name":"root","position":0});
	 var len2 = arr.length-1;
	 for (i=len2;i>=0;i--)
	    $('#bread').append('<li class="breadcrumb-item'+(i==0 ? ' active':'')+'"><a '+ 'class="clickable" onclick="SelectPlaylist(' + arr[i].num + ',1);"'+'>'+arr[i].name+'</li>');
//------------------------------------
         for (i=kk;i<len;i++) {
	    if (Drives[i].level <= ll) {
		break
	    } else {
		var name = Drives[i].path.split('/').pop();
		var exp = Drives[i].expanded;
		if (name!='') {
		  $('#Folders').append('<tr class="playliststr"'+(exp == 2 ? '><td class="type exp">':' onclick="SelectPlaylist(' + i + ')"><td class="type">')+
        	'<img src="img/_folder_open_24.png"></td><td class="title"><div>'+name + '</div></td>'+
        	'<td class="action"><a class="ripple" onclick="playadd(true,'+i+',event);" title="add"><img src="img/_add_24.png"></a></td>'+
        	'<td class="action"><a class="ripple" onclick="playadd(false,'+i+',event);" title="play"><img src="img/_play_24.png"></a></td></tr>');
        	}
	    }
         }
         for (i=0;i<len;i++) {
    	    if (Drives[i].path == '')  {
	       var name = Drives[i].name.replace('.ap2','');
	       if (name == 'aplayer.dat') name ='Last playlist';
    	       $('#Playlists').append('<tr class="playliststr" onclick="SelectPlaylist(' + i + ')"><td class="type">' + 
        	'<img src="img/_queue_24.png"></td><td class="title"><div>'+name + '</div></td>'+
        	'<td class="action"><a onclick="playadd(false,'+i+',event);" title="play"><img src="img/_play_24.png"></a></td></tr>');
    	    }
	    else
		break;
         }
	 var scr = document.getElementById('scrollbox');
	 if (curlevel > len2+1) {
	    scr.scrollTop = leveltop[len2+1];
	    }
	 else if (curlevel < len2+1)
	    scr.scrollTop = 0;
	 curlevel = len2 + 1;
    }
    RemoveHourglass();
}

function MakeHourglass()
{
     $('#spinner').show();
     hasHGlass = true;
}

function RemoveHourglass()
{
    $('#spinner').hide();
    hasHGlass = false;
}

function ReturnToSongs()
{
    $('#Input').css('display', 'none');
    $('#Filter').css('display', 'block');
    $('#Topcontrols').css('display', 'block');    
    if (OldViewPictures)
	ChangeImage();
//        OnPictureMode();
    if (!AddMode && PlaylistIndex != -1) {
          $('#Albums').empty();
          $('#Songs').empty();
//          console.log('songs empty');
    }
}

function openLib(){
    if (phone)
	mySwiper.scroll(2,true)
    else {
	if (mySwiper.index == 0) {
	    mySwiper.scroll(2,true);
	    window.setTimeout(function(){
	       $('#scrollbox').focus();
	    },800);
	}
	else
	    mySwiper.scroll(0,true);
	    window.setTimeout(function(){
	       $('#SongsBlock').focus();
	    },700);
    }
}

function play_(mode,index) {
  if (mode){ //add 
    AddFolder = true;
    $.getJSON('?Get_Playlist&playlist=' + index, ParseStd);
  }
  else {
    AutoPlay = true;
    CurrentSong = 0;
//    CurrentAlbum = -1;
    $.getJSON('?GetPlaylist&playlist=' + index, ParseStd);
  }
}

function playadd(mode,index,event) {
  event.stopPropagation();
//  ModePlaylists = true;
  MakeHourglass();
//  AddMode = mode;
  CurrentSong = 0;
  CurrentAlbum = 0;
  NeedAlbums = true;
  if (RadioMode) { 
    OnRadio();
    setTimeout(play_(mode,index), 2000);
  }
  else
     play_(mode,index);
}

function mode2(){
    var mode = $('#modePL').attr('data-mode');
    if (mode == 'lib') {
	$('#modePL').attr('data-mode','plist');
	$('#modePL').text('Playlists');
	$('#breadWrapper').hide();
	$('#Folders').hide();
	$('#Playlists').show();
    } else {
	$('#modePL').attr('data-mode','lib');
	$('#modePL').text('Music Library');
	$('#Playlists').hide();
	$('#breadWrapper').show();
	$('#Folders').show();
    }
}

function OnSelect(add)
{
    if (hasHGlass)
	return;
	
    if (ModePlaylists == false)
    {

        PlaylistIndex = PlaylistIndexNew = -1;
//        console.log(-1);
//      }
      $.getJSON('?GetPlaylists&ind=-1', fillFolders);
//      if (!root) ModePlaylists = true;
      ModePlaylists = true;
//      OldViewPictures = ViewPictures;
    }
    else
    {
    	if (PlaylistIndexNew != -1)
   	   StopCommand(ParseStd);

        if (PlaylistIndexNew == null)
    	    PlaylistIndexNew = 0;
        PlaylistIndex = PlaylistIndexNew;
    
	
        if (PlaylistIndex != -1)
        {
	      MakeHourglass();
        }
//        else
  //      {
            ModePlaylists = false;
            if (!phone) {
              $('#Albums').css('visibility', 'visible');
              $('#Songs').css('display', '');
              ReturnToSongs();
              $('#Topcontrols').css('display', 'block');
            }
//        }

    }

}

function ChangeImage()
{
    console.log('changeimage()');
    img = new Image();
    var imgstr = '?GetImage&pl=' + PlaylistIndex + '&image=' + CurrentAlbum + '&time=' + (new Date()).getTime();
    img.src = imgstr;
    img.onload = function() {
      $('#Image').attr('src',imgstr);
        $('#Image').attr('data-width',img.width);
        $('#Image').attr('data-height',img.height);
      $('#bg').css('background-image',"url('"+imgstr+"')");
    };
}


function OnViewAlbum()
{
//   console.log('onviewAlbum');
   if (!Playing)
        return;
    var trackinfo = $('#lSong').text();
    var $tempinp = $("<input id='inptmp'>");
    $("body").append($tempinp);
    $tempinp.val(trackinfo);
    var tempinput=document.getElementById('inptmp');
    tempinput.focus();
    tempinput.setSelectionRange(0, tempinput.value.length);
    document.execCommand("copy");
    $tempinp.remove();	
   if (CurrentAlbum != PlayingAlbum)
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
    if (!Playing) {
        $('#progress')[0].value = 0;
        $('#progress').css('background','rgba(0,0,0,0.3)');
    }
    else
    {
        ChangePosition = true;
        PositionCommand(pos);
        CurrentTime = PlayingLen / 1000 * pos / 100;
    }
}

function GetLenText(len) // Время в формате 0:00:00
{
    var Playtime = len / 1000; // sec
    var result = '';
    if (Playtime >= 3600)
    {
	result = result + Math.round(Playtime/3600 - 0.5) + ':';
	Playtime = Playtime%3600;
    }
    result = result + (Playtime >= 600 ? Math.round(Playtime/60 - 0.5):'0' + Math.round(Playtime/60 - 0.5))+':';
    var ost = Math.round(Playtime % 60 - 0.5);

    result = result  + (ost > 9 ? ost: '0' + ost);
    return result;
}

function ParseSongs(data)
{
//    console.log('ParseSongs');
    var Songs = data.Songs;
    var SongsLen = data.SongsLen;
    var slen;
    var val2 = '';
    CurrentSongs = Songs;
    $('#Songs').empty(); 

    if (Songs != null && Songs.length > 0)
    {
        $.each(Songs, function (ind, val) {
           slen = GetLenText(SongsLen[ind]);
	   val2 = val.replace('.flac',' ');
	   if (RadioMode) //RadioMode
             $('#Songs').append('<tr class="songstr" onclick="SelectSong(' + ind + ',false);PlayCommand2(' + ind + ');"><td class="sNum">'+(ind+1)+'</td><td class="sTitle">' + val2 + '</td><td class="sEdit"><div class="edit" onclick="MinusCommand(event)"><img src="img/_edit_24.png"></div></td></tr>')
	   else
             $('#Songs').append('<tr class="songstr" onclick="SelectSong(' + ind + ',false);PlayCommand2(' + ind + ');"><td class="sNum">'+(ind+1)+'</td><td class="sTitle">' + val2 + '</td><td class="sLen">'+slen+'</td></tr>'); 
        }); // ondblclick="SelectSong(' + ind + ')"
	if (Playing && CurrentAlbum == PlayingAlbum)
        {
            SelectSong(PlayingSong,true);
        }
        else {
            if (bFirst)
                  SelectSong(SavedSongIndex,true);
            else
               SelectSong(-1,true);
        }
    }
    else
        CurrentSong = null;
}

function ParseAlbums(data)
{
//    console.log('ParseAlbums');
    ModePlaylists = false;
    $('#Albums').css('visibility', 'visible');
    $('#Songs').css('display', '');
    $('#Topcontrols').css('display', 'block');
    Albums.Albums = data.Albums;
//    if (AutoPlay && Albums.Albums.length == 0)
//	return;

    Albums.Authors = data.AuthorList;
    Albums.Songs = data.Songs;
    Albums.AlbumsS = data.AlbumList;
    Albums.Years = data.YearList;
    Playlists = data.Playlists;
    SavedAlbumIndex = data.SavedAlbumIndex;
    SavedSongIndex = data.SavedSongIndex;

    RadioMode = data.RadioMode;
    if (RadioMode)
    {
//        RadioMode = data.RadioMode;
        $('#bRadio').attr('src', 'img/radioa.png');
        $('#bMinus').hide();
        $('#bSave').hide();
        $('#bPlus').show();
        $.each(Albums.Albums, function (ind, val) { 
    	    var pos = val.lastIndexOf('[');
    	    if (pos > 0) Albums.Albums[ind]=val.substring(0, pos-1);
    	});
    } else {
        $('#bRadio').attr('src', 'img/radiop.png');
        $('#bPlus').hide();
        $('#bMinus').show();
        $('#bSave').show();
    }

    $('#Albums').empty();
    $('#Songs').empty();
    if (Albums.Albums != null && Albums.Albums.length > 0) {
        CurrentAlbum = SavedAlbumIndex;
        var imgstr = '?GetImage&pl=' + PlaylistIndex + '&image='+ CurrentAlbum + '&time=' + (new Date()).getTime();
        img = new Image();
    	img.src = imgstr;
        img.onload = function() {
         $('#Image').attr('src',imgstr);
            $('#Image').attr('data-width',img.width);
            $('#Image').attr('data-height',img.height);

          $('#bg').css('background-image',"url('" + imgstr +"')");
        }
        $.each(Albums.Albums, function (ind, val) { $('#Albums').append('<option value=' + ind + ' title="'+ val +'" style="font-size:14px;">' + val + '</option>'); });
        $("#Albums option[value='"+ SavedAlbumIndex  + "']").prop('selected', true);
        if (Albums.Songs != null && Albums.Songs.length > 0)
        {
            bFirst = true;
            ParseSongs(Albums.Songs[0]);
            bFirst = false;
        }
    }
    else 
    {
        CurrentAlbum = null;
        $('#Image').attr('src','img/noimage.png');
          $('#Image').attr('data-width',-1);
          $('#Image').attr('data-height',-1);
    }

    //SelectSong(SavedSongIndex);
    RemoveHourglass(); 
   if(FlagUpdate)
   {
    	document.location = '/';    
      return;
    }
    UpdateState();
    if (AutoPlay) {
	PlayCommand();
//	SelectSong(-1);
	AutoPlay = false;
	if (mySwiper) mySwiper.scroll(0,true);
    }

}

function ParseStd(data)
{
    var res = data.Result;
    if (res != 'OK')
        DisplayError(res)
    UpdateState();
}

function PlayTimer() 
{
    if (ChangePosition)
        return;
    var len = PlayingLen;
    if (Playing)
    {
         if (!Paused)
         {
             CurrentTime = CurrentTime + 1;
             // Pictures rotator             
             if (!FlagRadio && Math.floor(CurrentTime) % PicturesInterval == 0 && CurrentAlbum == PlayingAlbum && ViewPictures)
             {
		 var imgstr = '?GetImage&pl=' + PlaylistIndex + '&image=' + CurrentAlbum + '&time=' + (new Date()).getTime();
		 img = new Image();
		 img.src = imgstr;
		 img.onload = function() {
                   $('#Image').attr('src',img.src);
    		     $('#Image').attr('data-width',img.width);
    		     $('#Image').attr('data-height',img.height);
                 }
             }

         }
         var posi = CurrentTime * 1000 / len * 100;
         $('#progress')[0].value = posi;
	 $('#progress').css('background','linear-gradient(to right, rgb(73,150,227) 0%, rgb(73,150,227) ' + posi + '%, rgba(0,0,0,0.3) ' + posi + '%, rgba(0,0,0,0.3) 100%)');
         $('#lTime').text(GetLenText(CurrentTime * 1000));
             current = new Date().getTime();
             if (current - timestamp > 2500)
                 len = 0;
             timestamp = current;
    }
    if (($('#lKhz').text() == '' || len /1000 - CurrentTime < 5  || $('#progress').css('visibility') == 'hidden' || CurrentTime < 5) &&Playing || Paused || hasHGlass) {
        UpdateState();
    }
    if (RadioMode && Playing && Math.floor(CurrentTime) % 10 == 0)
    {
        $.getJSON('?GetRadio', ParseRadio);
        if (Math.floor(CurrentTime) % PicturesInterval == 0 && CurrentAlbum == PlayingAlbum && ViewPictures)
        {
            UpdateRadioPicture();
        }
    }
}

function UpdateRadioPicture()
{
    if (RadioMode && Playing && ShowRPcs && RadPcs.length > 0 &&  CurrentAlbum == PlayingAlbum)
    {   
	    img = new Image();
	    img.src = RadPcs[IndRadPcs];
	    img.onload = function(){
	    	       if(img.width > 0) {
                  $('#Image').attr('src',img.src);
                  $('#Image').attr('data-width',img.width);
                  $('#Image').attr('data-height',img.height);
                }
            };
            IndRadPcs = IndRadPcs + 1;
       if (IndRadPcs >= RadPcs.length)
        	IndRadPcs = 0;
    }
    else
        ChangeImage();
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
   var tx = $('#lSong').text().trim();
    if (tx == '')
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
    if ($('#lSong').text() != txt) 
    {
        $('#lSong').text(txt);
        if (ShowRPcs && ViewPictures)
            ParseRadioPictures();
    }
}

function ParsePlay(data)
{
    CurrentTime = 0;
    if (PlayTimerId == null)
       SetTimer();
    var res = data.Result;
    if (res != 'OK')
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
    if (res != 'OK')
         DisplayError(res)
    Playing = false;

    PlayCommand();
}

function ChangeAlbum(obj)
{
//    console.log('changeAlbum');
    if (hasHGlass)
      return;
//    $('#lRadio').text('');
    CurrentAlbum = $(obj).val();
    $.getJSON('?GetSongs&album='+ CurrentAlbum, ParseSongs);
    UpdateRadioPicture();
}

function PlayCommand()
{
//    console.log('playCommand ' +CurrentSong);
//    if(CurrentAlbum == null || CurrentSongs == null || Playing || CurrentSongs.Length == 0 || ModePlaylists == true || hasHGlass)
    if (CurrentAlbum == null || CurrentSongs == null || Playing || CurrentSongs.Length == 0 || hasHGlass)
        return;
    PlayingSong = CurrentSong;
    PlayingAlbum = CurrentAlbum;
    if (CurrentSong < 0 || CurrentSong > CurrentSongs.length -1)
        PlayingSong=CurrentSong = 0;
    $('#lRadio').text(''); 
    RadPcs.length = 0;
     $.getJSON('?Play&album='+ CurrentAlbum+'&song='+CurrentSong, ParsePlay);
}

function PlayCommand2(ind)
{
    CurrentSong = ind;
    if (Playing)
        StopCommand(ParseStopPlay)
    else
        PlayCommand();
}

function SelectSong(ind, au)
{
    CurrentSong = ind;
    $.each($('#Songs').children(), function (ind2, val) {
        if (ind == ind2)
	    $(val).addClass('songactive')
        else
	    $(val).removeClass('songactive');
    });
   if(au)
    { 
        var h = $('#Songs tr').eq(0).height();
        $('#SongsBlock').scrollTop((ind-2)*h);
    }
}

function SelectPlaylist(ind, val)
{
    MakeHourglass();
    if (ind == -2) {
	var len = arr.length-1;
	for (i=0;i<len;i++){
	    $.getJSON('?GetPlaylists&ind=' + arr[i].num);
	}
	$.getJSON('?GetPlaylists&ind=-1',fillFolders);
	return;
    }
    if (PlaylistIndexNew != ind)
    {
        PlaylistIndexNew = ind;
    } 
//    else {
    leveltop[curlevel] = document.getElementById('scrollbox').scrollTop;
      if (val == undefined) {
	$.getJSON('?GetPlaylists&ind=' + ind, fillFolders)
      }
      else { 
	$.getJSON('?GetPlaylists&ind=' + ind);
	$.getJSON('?GetPlaylists&ind=' + ind, fillFolders);
      }
//    }
}

function StopCommand(parser)
{
    $('#lTime').text('');
    $('#lTotalTime').text('');
    $('#progress')[0].value = 0;
    $('#progress').css('background','rgba(0,0,0,0.3)');
//    StopTimer();
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
//    StopTimer();
    $('#progress')[0].value = 0;
    $('#progress').css('background','rgba(0,0,0,0.3)');
    //  Paused = false;
    $.getJSON('?Next', ParsePlay);
}

function PrevDown()
{
    $('#bPrev').css('opacity', '1');
    $('#bPrev').css('filter', 'invert(71%) sepia(8%) saturate(2318%) hue-rotate(137deg) brightness(259%) contrast(90%)');
}

function PrevUp()
{
    $('#bPrev').css('opacity', '');
    $('#bPrev').css('filter', 'none');
}

function PlayDown()
{
   $('#bPlay').css('opacity', '1');
   $('#bPlay').css('filter', 'invert(71%) sepia(8%) saturate(2318%) hue-rotate(137deg) brightness(259%) contrast(90%)');
   if (Playing)
     PauseCommand();
}

function PlayUp()
{
   $('#bPlay').css('opacity', '');
   $('#bPlay').css('filter','none');

   if (Playing) {
     if (!Paused)
        $('#bPlay').attr('src', 'img/pauser.png')
     else
        $('#bPlay').attr('src', 'img/playr.png');
   }
}

function NextDown()
{
   $('#bNext').css('opacity', '1');
   $('#bNext').css('filter', 'invert(71%) sepia(8%) saturate(2318%) hue-rotate(137deg) brightness(259%) contrast(90%)');
}

function NextUp()
{
    $('#bNext').css('opacity', '');
    $('#bNext').css('filter','none');
}

function PrevCommand()
{
    if (!Playing)
        return;
//    StopTimer();
    $('#progress')[0].value = 0;
    $('#progress').css('background','rgba(0,0,0,0.3)');
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
    setTimeout('HideError()', 3000);
}

function ParseVolume(data)
{
    ChangePosition = false;
}


function ParseState(data)
{
    ChangePosition = false;
    if (data.Scanning) {
       if (!hasHGlass) {
          MakeHourglass();
       }
    }
    else {
       if (hasHGlass) {
          RemoveHourglass();
          if (AddFolder) {
    	      AddFolder = false;
    	  }
    	 if(NeedAlbums)
    	   {
    	    $.getJSON('?GetAlbums', ParseAlbums);
    	    NeedAlbums = false;    
           }
          }
    }
    if (data.LastError.length > 0)
        DisplayError(data.LastError);
    if (Volume == null) {
        var vol = document.getElementById('volume');
	vol.value = Volume = data.Volume; 
//	34 139 34
	vol.style.background = 'linear-gradient(to right, forestgreen 0%, ' + curColor(data.Volume*0.01) + ' '+ data.Volume + '%, rgba(0,0,0,0.3) ' + data.Volume + '%, rgba(0,0,0,0.3) 100%)';
	$('#volValue').text(data.Volume+'%');
     }
     if (RadioMode != data.RadioMode)
     {
         RadioMode = data.RadioMode;
         if (RadioMode) {
             $('#bRadio').prop('src', 'img/radioa.png');
         }
         else {
             $('#bRadio').prop('src', 'img/radiop.png');
         }
     }
     if (RadioMode && (Boolean(ShowRPcs) != Boolean(data.RadioPictures)))
     {
        ShowRPcs = data.RadioPictures;     
        if (ShowRPcs && ViewPictures)
            ParseRadioPictures()
        else
	    UpdateRadioPicture();
    }
    
    if (!data.Playing)
    {
        Playing = false;
        $('#progressbox').hide();
        
        $('#lArtist').text('');
        $('#lArtist').attr('title','');
        $('#lAlbum').text('');
        $('#lAlbum').attr('title','');
        $('#lYear').text('');
        $('#lNum').text('');
        $('#lSong').text('');
        $('#lSong').attr('title','');
  	if (!hasHGlass) {       	
  	    $('#lFormat').text('');
            $('#lRadio').text('');
        }
        $('#lLength').text('');
        $('#lTime').text('');
        $('#bPlay').attr('src', 'img/playr.png')
        $('#tFormat').html('');

        CurrentTime = 0;
        PlayingLen = 0;
        PlayingName = '';
        $('#progress')[0].value = 0;
        $('#progress').css('background','rgba(0,0,0,0.3)');

    }
    else
    {
        $('#progressbox').show();
	    var new_alb = PlayingAlbum != data.Album;
        Playing = true;
        if (PlayTimerId == null)
             SetTimer();
        PlayingAlbum = data.Album;
       // var newsong = PlayingSong != data.Song;
        PlayingSong = data.Song;
        if (PlayingAlbum == CurrentAlbum) {      
        //    if(newsong)    
            	SelectSong(PlayingSong,true);
        }
        else {
            if (new_alb)
            {
                OnViewAlbum();              
                SelectSong(PlayingSong,true);
            }            
            else {
                SelectSong(-1,true);
            }
        }
        $('#lArtist').text(Albums.Authors[data.Album]);
        $('#lArtist').attr('title', $('#lArtist').text());
        var format = '';
        if (RadioMode) {
    	    $('#lRadio').hide();
            $('#lRadio').text('');
    	    $('#lAlbum').text(Albums.AlbumsS[data.Album]+': '+PlayingName);
    	    $('#lSong').text('');
	} else {
    	    $('#lRadio').show();
    	    $('#lAlbum').text(Albums.AlbumsS[data.Album]);
    	    $('#lAlbum').attr('title', $('#lAlbum').text());
    	    var lsong = '';
    	    if (PlayingName)
    	      if (PlayingName.indexOf('.flac') >-1) {
    		format = 'FLAC&emsp;&bull;&emsp;';
    		lsong = PlayingName.replace('.flac','');
    	      } else
    	      if (PlayingName.indexOf('.wav') >-1) {
    		format = 'WAV&emsp;&bull;&emsp;';
    		lsong = PlayingName.replace('.wav','');
    	      } else
    	        lsong = PlayingName;

    	    $('#lSong').text(lsong);
    	    $('#lSong').attr('title', $('#lSong').text());
    	}
        $('#lYear').text(Albums.Years[data.Album]);
        $('#lNum').text((data.Song+1).toString()+'. ');
        PlayingName = data.PlayingName;
        if (data.Freq > 0 && data.Bitrate > 0) 
            var s1 = data.Bitrate+' kbps'+'&emsp;&bull;&emsp;'+(data.Freq > 1000000 ? data.Freq / 1000000+' MHz':data.Freq/1000+' kHz')
        else
    	    var s1 = '';
        $('#tFormat').html(format + s1 + (s1 ? '&emsp;&bull;&emsp;':'')+ data.Bps+ ' bit' + (data.Ch > 2 ?'&emsp;&bull;&emsp;'+data.Ch+' ch':''));
//        $('#tFormat').show();

        PlayingLen = data.PlayingLen;
        $('#lLength').text(GetLenText(PlayingLen ));
        if (!(data.Position >= 0))
            data.Position = 0;
        if (RadioMode) 
            $.getJSON('?GetRadio', ParseRadio);
        CurrentTime = data.Position / 1000; // sec
        $('#lTime').text(GetLenText(data.Position));
        var progress_vis = data.ProgressVisible ? 'visible': 'hidden';
        $('#progress').css('visibility', progress_vis); 
	var posi = CurrentTime * 1000 / PlayingLen  * 100;
	$('#progress')[0].value = posi;
	$('#progress').css('background','linear-gradient(to right, rgb(73,150,227) 0%, rgb(73,150,227) ' + posi + '%, rgba(0,0,0,0.3) ' + posi + '%, rgba(0,0,0,0.3) 100%)');
	
        if (data.Paused)
        {
            Paused = true;
            $('#bPlay').attr('src', 'img/playr.png')
        }
        else
         {
            Paused = false;
            $('#bPlay').attr('src', 'img/pauser.png')
         }
         if (data.RadioTitle.length > 0 && RadioMode) {
            var txt = data.RadioTitle;
             if ($('#lSong').text() != txt) {
                $('#lSong').text(txt);
                if (ShowRPcs && ViewPictures)
                    ParseRadioPictures();
             }
            FlagRadio = true;
        }
         else
         {
             $('#lRadio').text('');
             $('#lRadio').text((CurrentSong + 1) + '/' + CurrentSongs.length);

             FlagRadio = false;
         }
    }
}

function UpdateState()
{
//    console.log('updateState->');
    $.getJSON('?State', ParseState);
}


function PlusCommand() {
    if (hasHGlass)
        return;
    if (RadioMode)
    {
        if(Playing)
				$.getJSON('?Favor', ParseStd);  
		  else {       
		  $('#reditok').css('display','none');
        $("#redittitle").text("Add Station");
        $('#rsect').val(Albums.Albums[CurrentAlbum]);
        $('#rname').val('');
        $('#rstat').val('');
        $('#rstat').text('');
        $('#rmess').hide();
        $("#reditrem").hide();
        $("#reditadd").show();
        myRedit.show();
   	  }
        return;
    }
    if (ModePlaylists == false)
    {
        $('#PlaylistName').val('');
        $('#Input').css('display', 'block');
    }
    else
    {
       OnSelect(true);
    }
}


function ParseStation(data)
{
    $('#rstat').val(data.url);
}

function OkCommand() {
    var str = $('#PlaylistName').val();
    if (str.length > 0) {
        PlaylistIndexNew = -1;
        $.getJSON('?SavePlaylist&name='+str, OnSelect);
    }
    mySaveplist.hide();
}

function MinusCommand(event) {
    event.stopPropagation();
    if(hasHGlass)
        return;
    if(RadioMode)
    {
	if(Playing)
	    StopCommand(ParseStd);
	    $('#reditok').css('display','block');
    	$("#redittitle").text("Delete Station");
        $('#rsect').val(Albums.Albums[CurrentAlbum]);
        $('#rname').val(CurrentSongs[CurrentSong]);        
        $('#rstat').val('');        
        $("#rmess").text('');
        $('#rmess').hide();
        $("#reditadd").hide();
        $("#reditrem").show();
        $.getJSON('?DeleteStationInfo&album='+ CurrentAlbum+'&song='+CurrentSong, ParseStation);
	myRedit.show();
        return;
    }
//    console.log(ModePlaylists + ' '+PlaylistIndexNew);
/*    if (ModePlaylists == true) {
      if (PlaylistIndexNew > 0)
        {
            var row = $("#Playlists").children().eq(PlaylistIndexNew);       
            if (row != undefined)
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
                        $.getJSON('?DeletePlaylist&name='+text, fillFolders);
                    }
                }
            }
        }
    }
    else {*/
     if(CurrentAlbum == null)
        return;
     $("#Search").val("");
     OldViewPictures = false;
     $.getJSON('?DeleteAlbum&album='+ CurrentAlbum, ParseAlbums);
//    }
}

function VolumeChange(vol)
{
    ChangePosition = true;
    if (vol > 100)
	vol = 100
    else
	if (vol < 0)
	    vol = 0;
    $('#volume').css('background','linear-gradient(to right, forestgreen 0%, '+curColor(vol*0.01)+' ' + vol + '%, rgba(0,0,0,0.3) ' + vol + '%, rgba(0,0,0,0.3) 100%)');
    $('#volValue').text(vol +'%');

    VolumeCommand(vol);
}


function VolPlus() {
    var x = $('#volume')[0].value;
    var n =   parseInt(x)+10;
    if (n > 100) n = 100;
    $('#volume')[0].value = n;
    VolumeChange(n);
}

function VolSub() {
    var y = $('#volume')[0].value;
    var n = parseInt(y)-10;
    if (n < 0) n = 0;
    $('#volume')[0].value = n;
    VolumeChange(n);
}

function OnRadio() {
//    console.log('OnRadio');
    $('#lRadio').text('');
    $('#lSong').text('');
    RadPcs.length = 0;
    OldViewPictures = false;
    $.getJSON('?RadioMode', ParseAlbums);
}

//Config
var Config = null;

function OnLoadStatus(data)
{
    Config = data;
    $('#stat_root').text((Config.stat_root == 1) ? 'yes' : 'no');
    $('#stat_cores').text(Config.stat_cpus.toString());
    $('#stat_prio').text(Config.stat_prio.toString());
    $('#stat_nice').text(Config.stat_nice.toString());
    $('#stat_16bit').text((Config.stat_16bit == 1) ? 'yes' : 'no');
    $('#stat_24bit').text((Config.stat_24bit == 1) ? 'yes' : 'no');
    $('#stat_32bit').text((Config.stat_32bit == 1) ? 'yes' : 'no');
    var is_playing = Config.stat_playing == 1;
    $('#stat_play').text(is_playing ? 'yes' : 'no');
    if (is_playing)
    {
      $('#status tr.playing').show();
      $('#stat_play_file').text(Config.stat_file);
      $('#stat_period_size').text(Config.stat_period_size + ' frames, ' + Config.stat_period_time + ' µs');
      $('#stat_buffer_size').text(Config.stat_buffer_size + ' frames, ' + Config.stat_period_time*Config.stat_buffer_size/Config.stat_period_size  + ' µs');
    }
    else
    {
      $('#status tr.playing').hide();
      $('#stat_play_file').text('');
      $('#stat_period_size').text('');
      $('#stat_buffer_size').text('');
    }

}

function init_config(){
    Config = new Object();
    $.getJSON('?GetConfig', OnLoadConfig);
}

function OnLoadConfig2(data){
    rootfolder = data.root_folder;
    RadioMode = data.RadioMode;
    if (rootfolder != '') root = true;
    if (data.volume_enabled) 
	$('#volumebox').css('display','flex');
    $('#Playlists').hide();
    OnSelect(false); // first run
}

function parseState2(data){
    Playing = data.Playing;
    PlayingSong = data.Song;
    Paused = data.Paused;
    RadioMode = data.RadioMode;
    //Position;
//    $.getJSON('?GetAlbums', ParseAlbums);
}
function checkroot(){
    Config = new Object();
    $.getJSON('?State',parseState2);
    $.getJSON('?GetConfig', OnLoadConfig2);
}


function ShowConfig()
{
    if (Config == null)
        init_config()
    else
        $.getJSON('?GetConfig', OnLoadConfig);
    myConfig.show();
}

function OnLoadConfig(data)
{
    Config = data;
    $('#covers').val(Config.covers);        
    if (Config.di_mode == 1)
        $('#radio_di').prop('checked','checked')
    else
    if (Config.di_mode == 2)
        $('#radio_fm').prop('checked','checked')
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
    if (Config.affinity_mode == 1)
        $('#cores1').prop('checked','checked')
    else
    if (Config.affinity_mode == 2)
        $('#cores2').prop('checked','checked');
    else
        $('#cores0').prop('checked','checked');
    $('#stat_root').text((Config.stat_root == 1) ? 'yes' : 'no');
    $('#stat_cores').text(Config.stat_cpus.toString());
    $('#stat_prio').text(Config.stat_prio.toString());
    $('#stat_nice').text(Config.stat_nice.toString());
    $('#stat_16bit').text((Config.stat_16bit) ? 'yes' : 'no');
    $('#td16').css('visibility',(Config.stat_16bit && (Config.stat_24bit || Config.stat_32bit)) ? 'visible' : 'hidden');
    $('#td24').css('visibility',(Config.stat_24bit && Config.stat_32bit) ? 'visible' : 'hidden');
    $('#stat_24bit').text((Config.stat_24bit) ? 'yes' : 'no');
    $('#stat_32bit').text((Config.stat_32bit) ? 'yes' : 'no');
    $('#stat_dsd').text('no');
    if ((Config.stat_dsd32be == 1))
            $('#stat_dsd').text('DSD_U32_BE');
    if ((Config.stat_dsd32le == 1))
            $('#stat_dsd').text('DSD_U32_LE') ;
    var can_native = Config.stat_dsd32le ==1 || Config.stat_dsd32be==1;
    $('#dsd_n').prop('disabled',can_native ? false : true);
    $('#sdm_n').prop('disabled',can_native ? false : true);
    var is_playing = Config.stat_playing == 1;
    $('#stat_play').text(is_playing ? 'yes' : 'no');
    if (is_playing)
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
      $('#stat_play_file').text('');
      $('#stat_period_size').text('');
      $('#stat_period_time').text('');
      $('#stat_buffer_size').text('');
    }
    $('#CardNum').text('');
    $('#asound').text('');
    if (Config.asound != undefined && Config.asound != null)  
        Config.asound.forEach(function(element) {
        $('#asound').append(element.toString()); 
        $('#asound').append('<br>');
        }, Config.asound);
    $('#cards').text('');
    if (Config.cards != undefined && Config.cards != null)
        Config.cards.forEach(function(element, i) {
        $('#cards').append(element.toString());
        $('#cards').append('<br>');
        if (i % 2)
            $('#cards').append('<br>');
        }, Config.cards);
    //DSP
    if (Config.multi_mode) 
	$('#check_multi').prop('checked','checked')
    else
	$('#check_multi').prop('checked','');
    if (Config.swap_mode) 
	$('#check_swap').prop('checked','checked')
    else
	$('#check_swap').prop('checked','');
    if (Config.phase_mode) 
	$('#check_phase').prop('checked','checked')
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
    if (Config.mmap == 1)    
        $('#mmap').prop('checked','checked')
    else
        $('#rw').prop('checked','checked');
    $('#enable_dsd').prop('checked',Config.enable_dsd ? 'checked' : '');
    $('#dsd_filter').val(Config.filter).change();
    $('#dsd_output').val(Config.output).change();
    $('#dsd_rate').val(Config.rate).change();
    $('#dsd_level').val(Config.level).change();
    $('#dsd_multithread').prop('checked',Config.multithread ? 'checked' : '');
    $('#root').val(Config.root_folder);
    if (Config.exists_conv == true) 
        $('#convtab').css('display','block')
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
        	           $('#hwvolume').parent().css('display','inline');
  						 // $('#lhw').css('display','inline');
  						  $('#hwlist').parent().css('display','inline');			  
        }
        else {
                    $('#hwvolume').parent().css('display','none');
  						 // $('#lhw').css('display','none');
  						  $('#hwlist').parent().css('display','none');
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
      $.get('/date.html', function(data) {$('#catdate1').text(data);}); 
}

function ExitApp()
{
  document.location = '/stop';
}

function StartPage2()
{
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
    if ($('#CardNum').val() != '')
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
    if ($('#cores1').is(':checked'))
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
    $.ajax({
            url:'SetConfig',
            type:'POST',
             data: JSON.stringify(arr),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json'
            }
        );
//    $('#config_label').text('Settings saved');
    setTimeout(function() { UpdateState();}, 2000);
    myConfig.hide();
}

function SelectCard()
{
   if (Config.stat_root != 1)
    {
        alert('Root User required!');
        $('#CardNum').val('').change(); 
        return;
    }
//    StopTimer();
    var num =  $('#CardNum').val();
    $.getJSON('?SelectCard&card=' + num, StartPage);    
}

function redit_ok(cmd)
{
  if (cmd == 'add')
  {
    var url = $('#rstat').val();
    var name = $('#rname').val();
    var folder = $('#rsect').val();
    if (url.indexOf('http://') == -1 &&  url.indexOf('https://') == -1)
    {
        $('#rmess').text('Please enter a network address (http://... or https://...)');
        $('#rmess').show();
    }
    else if (name == '')
    {
        $('#rmess').text('Please enter a station name');
        $('#rmess').show();
    }
    else if (folder == '')
    {
        $('#rmess').text('Please enter a section name');
        $('#rmess').show();
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
        });
        redit_cl()
    }
  }
  else
  {
      $.getJSON('?DeleteStation&album='+ CurrentAlbum+'&song='+CurrentSong, ParseAlbums);
      redit_cl();
  }
}

function redit_cl()
{
    myRedit.hide();
}

function AttChange(pos)
{
    document.getElementById('dbtx').innerText = pos;
}

var myConfig;
var myRedit;
var mySaveplist;
var mySwiper;

function onResize(){
    document.querySelector(':root').style.setProperty('--vh',window.innerHeight/100+'px');
    if (!phone) {
     mySwiper.destroy();
        mySwiper = new Swiper('#all',{
	    direction: 'horizontal',
	    touchRatio:0.5,
	    slidesPerView:2,
        });
    }
}

function doOnOrientationChange(){
   if (phone) {
     mySwiper.destroy();
     setTimeout(function() {
       mySwiper = new Swiper('#all',{
	direction: 'horizontal',
	touchRatio:0.5,
	slidesPerView:(window.innerWidth > window.innerHeight ? 2 : 1),
       });
     },700);
   }
}

function Init(){
  myConfig = new Modal(document.getElementById('config_body'));
  myRedit = new Modal(document.getElementById('redit'));
  myPict = new Modal(document.getElementById('picturebox'));
  mySaveplist = new Modal(document.getElementById('saveplist'));
  var w = window.innerWidth > window.innerHeight ? window.innerHeight : window.innerWidth;
  if (phone & w > 560) phone = false;

  checkroot();
  if (phone) {
    document.body.classList.add('phone');
    var menu = document.getElementById('menu');
    var opensel = document.getElementById('opensel');
    var plist = document.getElementById('plist');
    plist.insertBefore(menu,opensel);
  } else {
    $('#SongsBlock').focus();
  }

  mySwiper = new Swiper('#all',{
	direction: 'horizontal',
	touchRatio:0.5,
	slidesPerView: (phone && window.innerWidth < 560 ? 1 : 2),
	loop:true
  });

  if (iOS) {
    const ranges = RangeTouch.setup('input[type="range"]');
  }

  document.getElementById('breadWrapper').addEventListener('touchmove',function(event){
    event.stopPropagation();
  });

  document.getElementById('volumebox').addEventListener('mousedown',function(event){
    event.stopPropagation();
  });
  document.getElementById('progressbox').addEventListener('mousedown',function(event){
    event.stopPropagation();
  });
  document.getElementById('volume').oninput = function() {
	this.style.background = 'linear-gradient(to right, rgb(73,150,227) 0%, rgb(73,150,227) ' + this.value + '%, rgba(0,0,0,0.3) ' + this.value + '%, rgba(0,0,0,0.3) 100%)';
  };

  document.getElementById('bRadio').addEventListener('click',OnRadio);
  document.getElementById('bPlus').addEventListener('click',PlusCommand);
  document.getElementById('bSettings').addEventListener('click',ShowConfig);
  document.getElementById('bOpen').addEventListener('click',openLib);
  document.getElementById('InfoBlock').addEventListener('click',OnViewAlbum);
  document.getElementById('modePL').addEventListener('click',mode2);
  document.getElementById('bMinus').addEventListener('click',function(e) {MinusCommand(e);});
  document.getElementById('bSave').addEventListener('click',PlistShow);
  document.getElementById('bMinus2').addEventListener('click',function(e) {MinusCommand(e);});
  document.getElementById('bSave2').addEventListener('click',PlistShow);

  document.querySelector(':root').style.setProperty('--vh',window.innerHeight/100+'px');
  window.addEventListener('resize',onResize);
  window.addEventListener('orientationchange', doOnOrientationChange);

  var image1 = document.getElementById("Image");
  image1.addEventListener('click',OnPicture);
  if ('ontouchstart' in document) 
    document.querySelector('body').classList.remove('no-touch');

  if (iOS) {
    var mybox =  document.getElementById("picturebox");
    mybox.addEventListener('hide.bs.modal', function(event){
	var block = document.getElementById("ImgBlock");
	var image1 = document.getElementById("fullimg");
	hammertime.off('pan');
	hammertime.off('doubletap');
	hammertime.off('pinch');
	$('#Image2').hide();
	image1.id = 'Image';
	block.appendChild(image1);
	$('#Image').css('width','');
	$('#Image').css('height','');
	$('#Image').css('transform','');
	$('#Image').css('touch-action','');
	$('#Image').css('user-select','');
	$('#Image').css('-webkit-userdrag','');
	$('#Image').css('-webkit-tap-highlight-color','');
	image1.addEventListener('click',OnPicture);

    },false);
  }
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

