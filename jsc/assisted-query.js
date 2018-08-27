/**
 * 
 */
$(document).ready(function(e){
	
	var p_options=$('#p_options').attr('value');
	
	var s_options=$('#s_options').attr('value');
	//variables
	var criteriaHtml1='<div class="column span-1 last">&nbsp;&nbsp;</div><div class="criterion status assisted_query_part column span-22"><div class="assisted_query_part column span-24 last"><div class="assisted_query_part column span-11"><div class="mot_table assisted_query_part column span-6"> <select class="column span-24 last" id="p_attr" name="p_attr[';
	var criteriaHtml2='][]"><option value="word" selected="selected">forme</option>'+p_options+'</select> </div><div class="assisted_query_part column span-2"><select id="eq" name="eq[';
	var criteriaHtml3='][]"><option value="equal" selected="selected">=</option><option value="notequal" >&ne;</option></select></div><div class="assisted_query_part column span-12 last"><input name="mot[';
	var criteriaHtml4='][]" id= "mot" type="text" value=".*"/></div></div><div class="assisted_query_part column span-12 last"><div class="assisted_query_part column span-16"><select class="column span-24 last" id ="noD" name = "noD[';
	var criteriaHtml5='][]"> <option value="no" >sensible aux accents</option><option value="oui" selected="selected">pas sensible aux accents</option></select><select class="column span-24 last" id="noC" name = "noC['
	var criteriaHtml6='][]"><option value="no" >sensible à la casse</option><option value="oui" selected="selected">pas sensible à la casse</option></select></div><div class="assisted_query_part column span-6 last"><div class="assisted_query_part status" style="text-align:center; font-size:12pt" id="removeC">&cross;</div></div></div></div>';

	
	
	
	var motHtml1='<div class="column  span-1 last">&nbsp;&nbsp;</div><div  class="assisted_query_part token status column span-22" data-mot-index="';
	var motHtml2='"> <input  name="token_type[';
	var motHtml3= ']" type="hidden" value="token"><div class="assisted_query_part column span-4" style="font-size:12pt;font-color:#cccccc">Token:</div><div  class="assisted_query_part column prepend-16 span-4 last"><div id="removeMot" class="assisted_query_part status" style="text-align:center; font-size:12pt">Enlever</div></div><div class="column  span-1 last">&nbsp;&nbsp;</div><div class="criteria status assisted_query_part column span-22"><div class="column  span-1 last">&nbsp;&nbsp;</div><div class="criterion status assisted_query_part column span-22"><div class="assisted_query_part column span-24 last"><div class="assisted_query_part column span-11"><div class="mot_table assisted_query_part column span-6"> <select class="column span-24 last" id="p_attr" name="p_attr[';
	var motHtml4='][]"><option value="word" selected="selected">forme</option>'+p_options+'</select> </div><div class="assisted_query_part column span-2"><select id="eq" name="eq[';
	var motHtml5='][]"><option value="equal" selected="selected">=</option><option value="notequal" >&ne;</option></select></div><div class="assisted_query_part column span-12 "><input name="mot[';
	var motHtml6='][]" id= "mot" type="text" value=".*"/></div></div><div class="assisted_query_part column span-12 last"><div class="assisted_query_part column span-16"><select class="column span-24 last" id ="noD" name = "noD[';
	var motHtml7='][]"> <option value="no">sensible aux accents</option><option value="oui" selected="selected">pas sensible aux accents</option></select><select class="column span-24 last" id="noC" name = "noC[';
	var motHtml8='][]"><option value="no" >sensible à la casse</option><option value="oui" selected="selected">pas sensible à la casse</option></select></div><div class="assisted_query_part column span-6 last"><div class="assisted_query_part status" id="addCriteria" style="text-align:center; font-size:12pt" >+ Critères</div></div></div></div></div></div><div class="column  span-1 last">&nbsp;&nbsp;</div><div class="status assisted_query_part column span-22"><div class="assisted_query_part column prepend-2 span-10"><span class="column span-6">de: </span><input class="column span-8" type="text" onkeypress=\'return event.charCode >= 48 && event.charCode <= 57\' id="repMin" name= "repMin[';
	var motHtml9=']" value=1 /></div><div class="assisted_query_part column span-10 last"><span class="column span-6">à: </span><input class="column span-8" type="text" onkeypress=\'return event.charCode >= 48 && event.charCode <= 57\' id="repMax" name="repMax[';
	var motHtml10=']" value=1 /></div></div></div>';
	
	var structHtml1='<div class="column  span-1 last">&nbsp;&nbsp;</div><div class="token assisted_query_part status column span-22" data-mot-index="';
	var structHtml2= '"> <input  name="token_type[';
	var structHtml3=']" type="hidden" value="struct"><div class="assisted_query_part column span-4" style="font-size:12pt;font-color:#cccccc">Structure:</div><div  class="assisted_query_part column prepend-16 span-4 last"><div id="removeMot" style="text-align:center; font-size:12pt" class="assisted_query_part status">Enlever</div></div><div class="column span-1 last">&nbsp;&nbsp;</div><div class=" criteria status assisted_query_part column span-22 last"><div class="column span-1 last">&nbsp;&nbsp;</div><div class=" criterion status assisted_query_part column  span-22 last"><div class=" assisted_query_part column span-16"><div class="mot_table assisted_query_part column span-8"> <select class="column span-24 last" id="p_attr" name="p_attr[';
	var structHtml4='][]">'+s_options+'</select> </div><div class="assisted_query_part column span-2"><select id="eq" name="eq[';
	var structHtml5='][]"><option value="equal" selected="selected">=</option><option value="notequal" >&ne;</option></select></div><div class="assisted_query_part column span-12 last"><input name="mot[';
	var structHtml6='][]" id= "mot" type="text" value=".*"/></div></div><div class="assisted_query_part column span-6 last"><div class="assisted_query_part column span-22"><select class="column span-24 last" id ="noD" name = "noD[';
	var structHtml7='][]"> <option value="no" selected="selected">début</option><option value="oui" >fin</option></select><select style="display:none" class="column span-24 last" id="noC" name = "noC[';
	var structHtml8='][]"><option value="no" selected="selected">pas sensible à la casse</option><option value="oui" >sensible à la casse</option></select></div><div style="display:none" class="column prepend-1 span-10 last"><div class="assisted_query_part status" id="addCriteria">+ Critères</div></div></div></div></div></div></div><div style="display:none" class=" repeates status  column  span-24 last"><div class="assisted_query_part column prepend-2 span-10"><span class="column span-6">de: </span><input class="column span-8" type="text" onkeypress=\'return event.charCode >= 48 && event.charCode <= 57\' id="repMin" name= "repMin[';
	var structHtml9=']" value=1 /></div><div class="assisted_query_part column span-10 last"><span class="column span-6">à: </span><input class="column span-8" type="text" onkeypress=\'return event.charCode >= 48 && event.charCode <= 57\' id="repMax" name="repMax[';
	var structHtml10=']" value=1 /></div></div></div>';
	
	
    var maxCriteria = 4;
	
	var noC=1;
	var maxMot = 6;
	
	var noM = 0;
	
	var nextMotIndex=1;
	
	
	 
	 $("#full_query").on('click','#addCriteria',function(e){
	  var noCtmp = $(this).parent().parent().parent().parent().parent().children().length/2;
	  var motIndex = $(this).parent().parent().parent().parent().parent().parent().attr('data-mot-index');
	
	 if(noCtmp < maxCriteria){
	    var criteriaHtml=criteriaHtml1+motIndex+criteriaHtml2+motIndex+criteriaHtml3+motIndex+criteriaHtml4+motIndex+criteriaHtml5+motIndex+criteriaHtml6;
		$(this).parent().parent().parent().parent().parent().append(criteriaHtml);
		
		//$(this).parent('td').parent('tr').parent().children().last().before(criteriaHtml);
		//noC++;
		}else{
			alert("Vous pouvez avoir max. 4 critères par token");
		}
	});
	//remove criteria
	$("#full_query").on('click','#removeC',function(e){
		$(this).parent().parent().parent().prev().remove();
		$(this).parent().parent().parent().remove();
		//noC--;
	});
	
	//add word
	$("#full_query").on('click','#addMot',function(e){

	 if(noM < maxMot){
	  var motHtml= motHtml1 + nextMotIndex + motHtml2 + nextMotIndex + motHtml3 + nextMotIndex + motHtml4 + nextMotIndex + motHtml5+ nextMotIndex + motHtml6+ nextMotIndex + motHtml7+ nextMotIndex + motHtml8+ nextMotIndex + motHtml9 +nextMotIndex + motHtml10;
			//if(noM < 3){
			
				$("#first_line").append(motHtml);
			//}else{
			
			//	$("#second_line").append(motHtml);
			//}
	//	$(this).parent('td').parent('tr').parent().append(criteriaHtml);
		noM++;
		nextMotIndex++;
		}else{
			alert("Vous pouvez avoir max. 6  tokens ou/et structures");
		}
	});
	
	$("#full_query").on('click','#addStruct',function(e){

		 if(noM < maxMot){
		  var structHtml= structHtml1 + nextMotIndex + structHtml2 + nextMotIndex + structHtml3 + nextMotIndex + structHtml4 + nextMotIndex + structHtml5+ nextMotIndex + structHtml6+ nextMotIndex + structHtml7+ nextMotIndex + structHtml8+ nextMotIndex + structHtml9+ nextMotIndex + structHtml10;
				//if(noM < 3){
				
					$("#first_line").append(structHtml);
				//}else{
				
				//	$("#second_line").append(motHtml);
				//}
		//	$(this).parent('td').parent('tr').parent().append(criteriaHtml);
			noM++;
			nextMotIndex++;
			}
		});
		
	//remove word
	$("#full_query").on('click','#removeMot',function(e){
		//$(this).parent('td').parent('tr').parent().parent().parent().remove();
		$(this).parent().parent().prev().remove();
		$(this).parent().parent().remove();
		noM--;

		
	});
	
	$('#query').on("click",'#queryA',function(){

   	 $('#queryCQP').removeClass('selected');
   	$('#queryA').addClass('selected');
  
   	
   	$('#assisted_query').show();
   	$('.assisted_query_part').show();
   	$('#standard_query').hide();
   	$('.struct_repeat').hide();
   });
	
	$('#query').on("click",'#queryCQP',function(){

	   	 $('#queryA').removeClass('selected');
	   	$('#queryCQP').addClass('selected');
	  
	   	
	   	$('#assisted_query').hide();
	   	$('.assisted_query_part').hide();
	   	$('.struct_repeat').hide();
	   	$('#standard_query').show();
	   });
	
	
	$('#freqLookup').on("click",'#wordLu',function(){

	   	 $('#wordFl').removeClass('selected');
	   	$('#wordLu').addClass('selected');
	   	
		$('#wordLookup').show();
	 
	   	$('#frequencyList').hide();
	   
	   
	   });
		
	$('#freqLookup').on("click",'#wordFl',function(){

		   	 $('#wordLu').removeClass('selected');
		   	$('#wordFl').addClass('selected');
		   
		   	$('#wordLookup').hide();
			 
		   	$('#frequencyList').show();
		   	
		   });
});
