/* 
Copyright 2012 Denis Chenu for <http://www.sondages.pro>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
/* Function to filter a select by another select 
   In the same page
   var qID : the number of question to filter 
   var filterqID : the number of question filtering
*/
function selectFilterByCode(qID,filterqID){
  $(document).ready(function(){
    var idSelectFilter = $("#question"+qID).find("select").attr('id');
    $("#"+idSelectFilter).hide();
    var idSelectFiltering = $("#question"+filterqID).find("select").attr('id');
    if(typeof idSelectFilter === 'undefined' || typeof idSelectFiltering === 'undefined' )
    { 
      return false;
    }
    else
    {
      var idNewSelectFilter = 'select'+qID
      var NewSelectElement = "<select id='"+idNewSelectFilter+"'><option value=''>"+$("#"+idSelectFilter+" option[value='']:first").text()+"</option></select>";
      $("#"+idSelectFilter).after(NewSelectElement);
      $("#"+idNewSelectFilter).width($("#"+idSelectFilter).width());
      $("#"+idSelectFiltering).change(function(){
        $('#'+idSelectFilter).val('');
        $('#'+idSelectFilter).trigger('change');
        $('#'+idNewSelectFilter).val('');
        var valuefilter=$(this).val();
        $('#'+idNewSelectFilter+' option').not(':first').remove();
        $('#'+idSelectFilter+' option').each(function(){
          if($(this).attr('value').indexOf(valuefilter)==0){
            $(this).clone().appendTo('#'+idNewSelectFilter);
          }
        });
      });

      $("#"+idNewSelectFilter).change(function(){
        $('#'+idSelectFilter).val($(this).val());
        $('#'+idSelectFilter).trigger('change');
      });

      if($("#"+idSelectFiltering).val()!=''){
        var valuefilter=$("#"+idSelectFiltering).val();
        $('#'+idSelectFilter+' option').each(function(){
          if($(this).attr('value').indexOf(valuefilter)==0){
            $(this).clone().appendTo('#'+idNewSelectFilter);
          }
        });
        if($("#"+idSelectFilter).val()!=''){
           $('#'+idNewSelectFilter).val($("#"+idSelectFilter).val());
        }
      }
    }
  });
}
function selectFilterDualScale(qID){
  $(document).ready(function(){
    if($("#question"+qID).hasClass('array-flexible-duel-scale')){
      // Fix width of columns
      answertextwidth=$(this).find("col.answertext").attr('width').replace("%","");
      $(this).find("col.ddarrayseparator").attr('width',"2%");
      ddarrayseparatorwidth=$(this).find("col.ddarrayseparator").attr('width').replace("%","");
      var newwidth=(100-answertextwidth*1-ddarrayseparatorwidth*1)/2;
      $(this).find("col.dsheader").attr('width',newwidth+'%');
      $("#question"+qID+" table.question tbody tr").each(function(index){
        $(this).find("select").each(function(){
          //$(this).attr('id',$(this).attr('id').replace('#',"_"));
        });
        var idSelectFiltering = jqSelector($(this).find("select").eq(0).attr('id'));
        var idSelectFilter = jqSelector($(this).find("select").eq(1).attr('id'));
        var idNewSelectFilter = jqSelector('select'+qID+'_'+index);
        var NewSelectElement = "<select id='"+idNewSelectFilter+"'><option value=''>"+$("#"+idSelectFilter+" option[value='']:first").text()+"</option></select>";
        $("#"+idSelectFilter).hide();
        $("#"+idSelectFilter).after(NewSelectElement);
        $("#"+idNewSelectFilter).width($("#"+idSelectFilter).width());

        $("#"+idSelectFiltering).change(function(){
          $("#"+idSelectFilter).val('');
          $('#'+idNewSelectFilter).val('');
          var valuefilter=$(this).val().substring(0, $(this).val().length - 2);
          $('#'+idNewSelectFilter+' option').not(':first').remove();
          if($(this).val()==""){
            $('#'+idNewSelectFilter).hide();
          }else{
            $('#'+idNewSelectFilter).show();
            $("#"+idSelectFilter).find('option').each(function(){
              if($(this).attr('value').substring(0, $(this).attr('value').length - 2)==valuefilter){
                $(this).clone().appendTo('#'+idNewSelectFilter);
              }
            });
          }

        });
        $("#"+idNewSelectFilter).change(function(){
          $('#'+idSelectFilter).val($(this).val());
          saveval=$('#'+idSelectFiltering).val();
          $('#'+idSelectFilter).trigger('change');
          if($(this).val()==""){
            $('#'+idSelectFiltering).val(saveval);
            $('#'+idSelectFiltering).trigger('change');
            $('#'+idSelectFilter).val($(this).val(""));
          }
        });

        if($("#"+idSelectFiltering).val()!=''){
          var valuefilter=$("#"+idSelectFiltering).val().substring(0, $("#"+idSelectFiltering).val().length - 2);
          $('#'+idSelectFilter+' option').each(function(){
            if($(this).attr('value').substring(0, $(this).attr('value').length - 2)==valuefilter){
              $(this).clone().appendTo('#'+idNewSelectFilter);
            }
          });

          if($("#"+idSelectFilter).val()!=''){
             $('#'+idNewSelectFilter).val($("#"+idSelectFilter).val());
          }
        }else{
           $('#'+idNewSelectFilter).hide();
        }
      });
    }
  });
}
function jqSelector(str)
{
    return str.replace(/([;&,\.\+\*\~':"\!\^#$%@\[\]\(\)=>\|])/g, '\\$1');
}
