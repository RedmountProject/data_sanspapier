// mapping of validations errors for ajax forms
var SPFormMapper = (function(){
    var exposed = {
      "createErrorElements": function(form_id){
        // get parent for global errors
        var parent = $('#'+form_id).parent();
        parent.prepend("<div id='sp_global_error_"+form_id+"' class='sp_global_error'></div>");
        // set rooms in child
        var inputs = this.sortInputs(form_id);
        for(i in inputs){
          var id = $(inputs[i]).attr('id');
          // display:inline; should be global CSS 
          $(inputs[i]).parent().append("<div style='display:inline;' class='sp_local_error' id='sp_local_error_"+id+"' class='sp_global_error'></div>");
        }
      },
      "removeErrorElements": function(form_id){
        $("#sp_global_error_"+form_id).remove();
        var inputs = this.sortInputs(form_id);
        for(i in inputs){
          var id = $(inputs[i]).attr('id');
          $('#sp_local_error_'+id).remove();
        }  
      },
      "sortInputs":function(form_id){
        
        
        var ainputs = $('#'+form_id+' :input'); // fetch all inputs
        var inputs = []; // interesting inputs
        // sort inputs (remove duplicates and hidden)
        var keys = {};
        ainputs.each(function(idx,elem){
          var type = $(elem).attr('type');
          if( type !== 'hidden' && type !== 'submit' && keys[type] !== 'password' && keys[type] !== 'email'){
            inputs.push(elem);
            keys[type] = type;
          }
        });
        return inputs;
      },
      "replaceForm": function(form_id, content){
        // hide this ugly form
        $('#'+form_id).hide();
        var parent = $('#'+form_id).parent();
        parent.prepend("<div id='sp_global_message_"+form_id+"' class='sp_global_message'>"+content+"</div>");
      },
      "displayErrors": function(form_id, errors){
         var form = $('#'+form_id); // get the form
         var parent = $(form).parent(); // the div right above the form
         var inputs = this.sortInputs(form_id);

         for(i in inputs){
             var id = $(inputs[i]).attr('id');
             for(j in errors){
               // take care of form global error
               if(errors[j][0] == "global"){
                 $("#sp_global_error_"+form_id).html(errors[j][1]);
               }
               var exp = new RegExp(errors[j][0],"gi");
               
               if(id.match(exp) !== null){
                 $("#sp_local_error_"+id).html(errors[j][1]);
               }
             }
         }
         
      }
    }
    return exposed;
  })();
  
  function parseEEEdate(pDate)
  {
    var date = new Date(pDate.substring(0,3)+','+pDate.substring(3,11)+pDate.substring(pDate.length-4,pDate.length)+pDate.substring(10,19));
    var finalDate = ("0" + date.getDate()).slice(-2)+'/'+("0" + date.getMonth()).slice(-2)+'/'+date.getFullYear();
    return(finalDate);
  }