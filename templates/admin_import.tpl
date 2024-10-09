{cms_jquery}
<script type="text/javascript">
var progress_max = 100;
var progress_val = 0;
function progress_reset() {
  progress_max = 100;
  progress_val = 0;
  $('#progress').width(0);
}

function progress_total(num) {
  progress_max = num;
}

function progress(num_steps) {
  progress_val = progress_val + num_steps;
  var w = 0,t = 0;
  if( progress_max > 0 ) {
    t = progress_val / progress_max * 100;
    w = Math.round( t );
  }
  $('#progress').width(w+'%');
}

function error(msg) {
  $('#status_area').append('<p class="errormsg">'+msg+'</p>');
}

function status(msg) {
  $('#status_area').append('<p>'+msg+'</p>');
}

function stat(key,val)
{
  $('#legend').show().append('<tr><td>'+key+'</td><td>'+val+'</td></tr>');
}

</script>

<style type="text/css">
div.meter {
  margin-left: auto;
  margin-right: auto;
  margin-bottom: 1.5em;
  border: 1px solid black;
  background-color: #fff;
  height: 2em;
  color: yellow;
}
span#progress {
  display: block;
  background-color: cyan;
  height: 100%;
  overflow: hidden;
}

div#status_area {
  margin-top: 1em;
  margin-left: 2em;
  margin-right: 2em;
}
p.errormsg {
  background-color: pink;
  border: 1px solid gray;
  padding-left: 0.5em;
  padding-top: 0.25em;
  padding-bottom: 0.25em;
}
table.pagetable {
  margin-left: auto;
  margin-right: auto;
  margin-bottom: 1.5em;
}
table.pagetable > caption {
  margin-left: auto;
  margin-right: auto;
  font-weight: bold;
}
</style>

<div class="meter">
  <span id="progress"></span>
</div>

<table class="pagetable" id="legend" style="display: none;">
  <caption>Statistics</caption>
</table>

<div id="status_area">
</div>