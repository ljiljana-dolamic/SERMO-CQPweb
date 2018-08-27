/**
 * 
 */

/**
 * functing giving the help in popup
 * 
 */


$(function(){
	var URL = "doc";
	
  $('.modal-help').on('click', function(e){
    
    
    var docName = $(this).attr('data-help-name');
    var pdf_link="../doc/"+docName;
    var title = $(this).attr('title');
    $(".modal .modal-title").html(title);
    
//    $("#modal-frame").attr("src", "../doc/my_pdf.pdf");
    $(".modal .modal-body").html('<div class="iframe-container"><iframe src="'+pdf_link+'"></iframe></div>');
   
    $(".modal .modal-footer").html("");
    
    $(".modal").show();
    
  });
  
  
  
  $('.close-modal').on('click', function(e){
	   
	    $(".modal").hide();
	   
	   
	  });
  
});