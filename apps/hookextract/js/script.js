/**
 * ownCloud - hookextract
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexy Yurchanka <maly@abat.de>
 * @copyright Alexy Yurchanka 2016
 */

(function ($, OC) {

    $(document).ready(function () {
    	function show(page){
    		$("#page1").hide();
    		$("#page2").hide();
    		$("#page3").hide();
    		$(page).show();
    	}
    	
        debugger;
        show("#page1");
    	
    	var curDate = new Date();
        //var dateString = curDate.getFullYear() + "-" + ("0"+(curDate.getMonth()+1)).slice(-2) + "-" +
        //("0" + curDate.getDate()).slice(-2);
		//$("#to").val(dateString);
		
		var curDate2 = curDate.setMonth(curDate.getMonth() - 1);
		
		//dateString = curDate.getFullYear() + "-" + ("0"+(curDate.getMonth()+1)).slice(-2) + "-" +
		//("0" + curDate.getDate()).slice(-2);
		//$("#from").val(dateString);
    	
    	$('#from').datepicker({
			selectOtherMonths: true,
			dateFormat: 'yy-mm-dd',
			defaultDate: '-1m',
			minDate : -300
		});
		$('#to').datepicker({
			selectOtherMonths: true,
			dateFormat: 'yy-mm-dd',
			defaultDate: 0,
			minDate : -300
		});
		
		$('#begin_selection').datepicker({
			selectOtherMonths: true,
			dateFormat: 'yy-mm-dd',
			minDate : -300
		});
		$('#end_selection').datepicker({
			selectOtherMonths: true,
			dateFormat: 'yy-mm-dd',
			minDate : -300
		});
		
		begin_selection
		
		$("#id1").click(function(){
			show("#page1");
		});
		$("#id2").click(function(){
			show("#page2");
		});
		$("#id3").click(function(){
			show("#page3");
		});
        
		
		$('#submit_timers').click(function () {
			var url = OC.generateUrl('/apps/hookextract/timers');
			
			$("#jobSettings")[0].action = url;
			$("#jobSettings").submit();
			
		});
		
		$('#setActive').click(function(){
			var url = OC.generateUrl('/apps/hookextract/timers');
			
			var input = $("<input>").attr("type", "hidden").attr("name", "active").attr("id", "active").val("+");
			$('#jobSettings').append($(input));
			$("#jobSettings")[0].action = url;
			$("#jobSettings").submit();
		});
		
		$('#setDeactive').click(function(){
			var url = OC.generateUrl('/apps/hookextract/timers');
			
			var input = $("<input>").attr("type", "hidden").attr("name", "active").attr("id", "active").val("-");
			$('#jobSettings').append($(input));
			$("#jobSettings")[0].action = url;
			$("#jobSettings").submit();
		});
    	
        $('#preselect').click(function () {
            var url = OC.generateUrl('/apps/hookextract/preselect');
            var data = {
                from: $("#from").val(),
                to: $("#to").val()
                
            };
            
            
            $.post(url, data).success(function (response) {
                $('#echo-result').html(response);
                $('#select').click(function () {
                    var url = OC.generateUrl('/apps/hookextract/select');
                    var data = {
                        datefrom: $("#from").val(),
                        dateto: $("#to").val(),
                        formtype: $("#presel").val().join(";"),
                        user: $("#user").val()
                    };

                    debugger;

                    $.post(url, data).success(function (response) {
                        $('#echo-selection').html(response);
                        $('#exceldownl').click(function () {
                            var url = OC.generateUrl('/apps/hookextract/xls');
                            var data = {
                                datefrom: $("#from").val(),
                                dateto: $("#to").val(),
                                formtype: $("#presel").val().join(";"),
                                user: $("#user").val()
                            };

                            debugger;

                            $('<form action="' + url + '" method="POST">' +
                                    '<input type="hidden" name="datefrom" value="' + $("#from").val() + '">' +
                                    '<input type="hidden" name="dateto" value="' + $("#to").val() + '">' +
                                    '<input type="hidden" name="formtype" value="' + $("#presel").val()[0] + '">' +
                                    '</form>').submit();
                        });
                    });
                });
            });
        });

        $('#select').click(function () {
            var url = OC.generateUrl('/apps/hookextract/select');
            var data = {
                datefrom: $("#from").val(),
                dateto: $("#to").val(),
                formtype: $("#presel").val().join(";"),
                user: $("#user").val()
            };

            debugger;

            $.post(url, data).success(function (response) {
                $('#echo-selection').html(response);

                $('#exceldownl').click(function () {
                    var url = OC.generateUrl('/apps/hookextract/xls');
                    var data = {
                        datefrom: $("#from").val(),
                        dateto: $("#to").val(),
                        formtype: $("#presel").val().join(";"),
                        user: $("#user").val()
                    };

                    debugger;

                    $.post(url, data).success(function (response) {
                        debugger;
                        $('#iframebox').html(response);
                    });

                });


            });


        });

        $('#exceldownl').click(function () {
            var url = OC.generateUrl('/apps/hookextract/xls');
            var data = {
                datefrom: $("#from").val(),
                dateto: $("#to").val(),
                formtype: $("#presel").val().join(";"),
                user: $("#user").val()
            };

            debugger;

            $.post(url, data).success(function (response) {
                $('#iframebox').html(response);
            });
        });

        $('#submit_upload').click(function () {
            var url = OC.generateUrl('/apps/hookextract/upload');
            var data = { filepath: $("#filepath").val() };
            
            debugger;
            
//            $('#fileUpload').submit();
            
            $.post(url, data).success(function (response) {
                $('#iframebox').html(response);
            });
        });
        
                $('#afterupl').dialog({
            title: "Alert",
            autoOpen: true,
            minHeight: 100,
            close: function(event, ui) {
                window.history.back();
            }
        });


    });

})(jQuery, OC);