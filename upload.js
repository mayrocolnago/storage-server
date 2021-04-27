var upfilequeue = [];
var upfilecountid = 0;
var upfilesendtimer = null;
var upfilescriptready = false;
var upresizescriptaddon = null;
var upfilescriptuploading = false;
var upstoragesessiontoken = '[TOKEN]';
var upstoragedefaultpathdir = '//[SERVER_NAME]/';
var upstoragedefault = upstoragedefaultpathdir;

function bindupload(element,params,onstart,ondone) {
    var elemparent = fupgetelemparent(element);
    if(elemparent != null) $(elemparent).attr('enctype','multipart/form-data'); else return;
    try { $(element).attr('name','file'); } catch(e) { console.log(e); }
    try { if(!params.f) params.f = 'upload'; if(!params.p) params.p = 'root/'; } catch(e) { params = {'f':'upload','p':'root/'}; }
    try { if(!params.token) params.token = upstoragesessiontoken; } catch(e) { }
    try { $(element).off('change'); } catch(e) { console.log('error unbinding upload',e); }
    try { $(element).on('change',function (event) { console.log('file upload triggered');
      for(var i = 0; i < event.target.files.length; i++) {
        var type = event.target.files[i].type.split('/'); 
        var name = event.target.files[i].name;
        var size = event.target.files[i].size;
        var elemparent = fupgetelemparent(this);
        var ictype = 'file'; console.log('queue: '+name);
        switch (type[0]) {
          case 'image': if((type[1] == 'png') || (type[1] == 'jpg') || (type[1] == 'jpeg')) ictype = 'photo'; break;
          case 'audio': ictype = 'audio'; break; case 'video': ictype = 'video'; break; }
        
        var retorno = { 'id':++upfilecountid, 'element':$(element).attr('id'), 'filename':name, 'filetype':ictype, 'filemime':type.join('/'), 'filesize':size, 'uptime':((new Date()).getTime() / 1000) };
        var filedata = null;
        
        if(!((ictype != 'photo') || (!upfilescriptready) || (!window.File || !window.FileReader || !window.FileList || !window.Blob)))
          filedata = event.target.files[i];
        else { try {
            if($(elemparent).prop('tagName').toLowerCase() == 'form') {
              var previouselementid = $(element).attr('id');
              var previousparentid = $(elemparent).attr('id');
              $(elemparent).attr('id','taupformelem'+upfilecountid);
              Object.keys(params).forEach(function (item) { 
                if(!($('#taupformelem'+upfilecountid+' input[name='+item+']').length))
                  $(elemparent).append('<input type="hidden" name="'+item+'" value="'+params[item]+'">');
                else $('#taupformelem'+upfilecountid+' input[name='+item+']').val(params[item]); });
              filedata = (new FormData( document.getElementById('taupformelem'+upfilecountid) ));
              $(elemparent).attr('id',previousparentid);
            } else console.log('could not find form block');
          } catch(e) { console.log('could not queue file ',e); } }
        
          if(filedata != null) retorno.filedata = filedata; else retorno = { 'id':0 };
            
          var resultfromstart = true;
          try { resultfromstart = onstart(retorno); } catch(e) { console.log('could not callback onstart upload script',e); }
          if((resultfromstart !== false) && (retorno.id)) { retorno.filedata = filedata; retorno.params = params; retorno.ondone = ondone; upfilequeue.push(retorno); }
        }
    });
    console.log('upload element binding complete');
  } catch(e) { console.log('could not bind file upload trigger',e); }
}

function fupgetelemparent(elemobj) {
  var elempdetect = elemobj; var esearchform = true; var result = null;
  var upelemid = 0; var upelemtag = ''; var currentformtag = '';
  while (esearchform) {
    upelemid++; if(upelemid > 9999) esearchform = false;
    try { upelemtag = $(elempdetect).prop('tagName').toLowerCase(); } catch(e){ upelemid++; }
    if((upelemtag == 'form') || (upelemtag == 'body') || (!esearchform)) break;
    try { elempdetect = $(elempdetect).parent(); } catch(e){ upelemid++; } }
  try { result = ($(elempdetect).prop('tagName').toLowerCase() == 'form') ? elempdetect : null; }
  catch(e) { console.log('could not bind. missing form element block',elemobj); }
  return result;
}

function fileuploadintervalfunc() {
  if(upfilescriptuploading) return;
  if(!upfilequeue[0]) return;
  
  upfilescriptuploading = true;
  var atual = upfilequeue[0];
  var retorno = new Object();
  Object.keys(atual).forEach(function (item) {
    if(!((item == 'ondone') || (item == 'params') || (item == 'filedata'))) retorno[item] = atual[item]; });
  retorno.sendtime = ((new Date()).getTime() / 1000);

  if((atual.filetype != 'photo') || (!window.File || !window.FileReader || !window.FileList || !window.Blob)) {
    /* upload files without conversion */
    try {
      $.ajax({ type: 'POST', url: upstoragedefaultpathdir+'upload.php', data: atual.filedata,
          cache: false, processData: false, contentType: false,
          success: function (updata) {
            retorno.elapsedtime = ((new Date()).getTime() / 1000) - retorno.sendtime;
            retorno.result = (updata.result) ? updata.result : '';
            try { atual.ondone(retorno); } catch(e) { console.log('could not callback ondone upload script',e); }
            upfilescriptuploading = false; upfilequeue.shift();
          },error: function(e) { upfilescriptuploading = false; } });
      } catch(e) { upfilescriptuploading = false; }
  } else {
    /* upload converted photos */
    upresizescriptaddon.photo(atual.filedata, 800, 'dataURL', function (imagem) {
      var paramsgo = { 'file': imagem, 'base64':'1' };
      Object.keys(atual.params).forEach(function (item) { paramsgo[item] = atual.params[item]; });
      try {
        $.post(upstoragedefaultpathdir+'upload.php', paramsgo).done(function(updata) { 
          retorno.elapsedtime = ((new Date()).getTime() / 1000) - retorno.sendtime;
          retorno.result = (updata.result) ? updata.result : '';
          try { atual.ondone(retorno); } catch(e) { console.log('could not callback ondone upload script',e); }
          upfilequeue.shift(); upfilescriptuploading = false;
         }).fail(function(e){ upfilescriptuploading = false;
        }).always(function(){ upfilescriptuploading = false; });
      } catch(e) { upfilescriptuploading = false; }
    });
  }
}

function fileupaddonload( url, callback ) {
  var script = document.createElement('script');
  script.type = 'text/javascript';
  if(script.readyState) {
    script.onreadystatechange = function() {
      if ( script.readyState === 'loaded' || script.readyState === 'complete' ) {
        script.onreadystatechange = null;
        callback(); }
    };
  } else script.onload = function() { callback(); };
  script.src = url;
  document.getElementsByTagName('head')[0].appendChild(script);
}
try {
  fileupaddonload(upstoragedefaultpathdir+'canvas.min.js',function(){
    fileupaddonload(upstoragedefaultpathdir+'resize.js',function(){
      upresizescriptaddon = new window.resize();
      upresizescriptaddon.init();
      upfilescriptready = true;
    });  
  }); } catch(e) { console.log('could not load upload addons',e); }

upfilesendtimer = setInterval(function(){
  try { fileuploadintervalfunc(); } catch(e) { } },1000);