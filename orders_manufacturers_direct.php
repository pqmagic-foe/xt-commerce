<?php
/* --------------------------------------------------------------
   
   Direct order 01.2015
   
   --------------------------------------------------------------*/

require('includes/application_top.php');

if(isset($_GET['step']) && $_GET['step'] == "loadData" && isset($_GET['mID'])){

    require('includes/classes/orders_manufacturers.php');

    $om = new orders_manufacturers();

    $manaufactures_emails = $om->get_manufacturers_emails($_GET['mID']);

    $emails = [];
    $m_content="";
    $email_count = 0;
    foreach($manaufactures_emails AS $me){
        $emails[] = [$me[0],$me[1]];
        $m_content .="<tr class='cframeLine'>";
        $m_content .="<td align='left'>".$me[0]."</td>";
        $m_content .="<td align='left'>".$me[1]."</td>";
        $m_content .="<td align='right'>benutzen? <input type='checkbox' name='memail[".$email_count."][use]' value='use'></td>"
            ."<input type='hidden' value='".$me[0]."' name='memail[".$email_count."][name]'>"
            ."<input type='hidden' value='".$me[1]."' name='memail[".$email_count."][email]'></tr>";



        $email_count++;

    }
    $company_data['emails'] = base64_encode($m_content);
    $company_data['customer_number'] = (string) $om->get_mo_order_number($_GET['mID'])['cnumber'];
    header('Content-type: text/html; charset=utf-8');
    echo json_encode($company_data);
    exit();

}

if(isset($_GET['step']) && $_GET['step']=='send'){

    if(($_POST['delivery_name'] != "" || $_POST['delivery_company'] != "")){

        $products = array();

        foreach($_POST['article'] as $product){
            if($product['IdNumber']!=""){
                $products[] = array("number"=>$product['IdNumber'],"description"=>$product['Description'],"count"=>$product['orderCount'],"memo"=>$product['Memo']);
            }

        }

        if(count($products)>0){
            // Datei-Upload-Verarbeitung
            $attachment = null;
            if(isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
                $attachment = array(
                    'name' => $_FILES['attachment']['name'],
                    'path' => $_FILES['attachment']['tmp_name'],
                    'type' => $_FILES['attachment']['type']
                );
            }

            require_once (DIR_FS_CATALOG.DIR_WS_CLASSES.'class.phpmailer.php');
            require_once (DIR_FS_INC.'xtc_php_mail.inc.php');

            //mails senden
            $smarty = new Smarty;
            $smarty->caching = false;

            $smarty->template_dir = DIR_FS_CATALOG.'templates';
            $smarty->compile_dir = DIR_FS_CATALOG.'templates_c';
            $smarty->config_dir = DIR_FS_CATALOG.'lang';

            $smarty->assign('PRODUCTS',$products);
            $smarty->assign('delivery_name',$_POST['delivery_name']);
            $smarty->assign('delivery_company',$_POST['delivery_company']);
            $smarty->assign('morder_regard',$_POST['morder_regard']);

            if($_POST['delivery_phone'] !=""){
                $smarty->assign('delivery_phone',"Lieferung bitte telefonisch avisieren lassen: ".$_POST['delivery_phone']);
            }
            $smarty->assign('morder_cnumber',$_POST['morder_cnumber']);
            $smarty->assign('morder_memo',$_POST['morder_memo']);
            $smarty->assign('clear_date',date("d.m.Y"));

            $smarty->assign('logo_path', HTTP_SERVER.DIR_WS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/img/');

            $html_mail = $smarty->fetch(CURRENT_TEMPLATE.'/admin/mail/german/orders_manufacturers_direct.html');
            $txt_mail = $smarty->fetch(CURRENT_TEMPLATE.'/admin/mail/german/orders_manufacturers_direct.txt');

            $subject = 'Komm: '.$_POST['morder_regard'];

            $emails_list = [];
            $list_count2 = 1;

            $arrAddress =Array(
                'delivery_name'=>$_POST['delivery_name'],
                'delivery_company'=>$_POST['delivery_company']
            );
            $arrAdditionalInformation = Array(
                'delivery_phone'=>$_POST['delivery_phone'],
                'morder_regard'=>$_POST['morder_regard'],
                'morder_cnumber'=>$_POST['morder_cnumber'],
                'morder_memo'=>$_POST['morder_memo']
            );

            //mail an jeden Empfaenger senden
            foreach($_POST['memail'] AS $maildata){

                if($maildata['use']){

                    $emails_list[] = $maildata['email'];


                    //$maildata['email'] = "matthias.tobies@googlemail.com";
                    $copy_mail = "info@kaelte-berlin.de";

                    xtc_php_mail('info@kaelte-berlin.de', 'Kälte-Berlin', $maildata['email'], $maildata['email'], '', 'info@kaelte-berlin.de', 'Kälte-Berlin', '', '', $subject, $html_mail, $txt_mail);

                    //sicherheitskopie

                    xtc_php_mail('info@kaelte-berlin.de', 'Kälte-Berlin', $copy_mail, $copy_mail, '', 'info@kaelte-berlin.de', 'Kälte-Berlin', '', '', $subject." Kopie ".$maildata['email']." ".$maildata['name'], $html_mail, $txt_mail);
                }
            }

            xtc_db_query("INSERT INTO `orders_manufacturers_direct` (`id`,`manufacturers`,`date`,`manufacturers_emails`,`products`,`delivery_address`,`additional_information`,`status`) VALUES ('','".$_POST['mID']."','".date("Y-m-d H:i:s")."','".  serialize($emails_list)."','".  serialize($products)."','".  serialize($arrAddress)."','".  serialize($arrAdditionalInformation)."','sent');");

            $messageStack->add("Mail erfolgreich versendet!", 'success');


        }else{
            $messageStack->add('Kein Produkt angegeben!', 'error');
        }


    }else{

        $messageStack->add('Name, Strasse, Plz, Ort und R&uuml;ckrufnummer sind Pflichtfelder', 'error');
    }
}

?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html <?php echo HTML_PARAMS; ?>>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $_SESSION['language_charset']; ?>">
        <title><?php echo TITLE; ?></title>
        <link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
        <script type="text/javascript" src="includes/general.js"></script>
        <script src="includes/transfer.js" type="text/javascript"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.js"></script>
        <script src="includes/jquery.activity-indicator-1.0.0.js" type="text/javascript"></script>
        <script>
            $(document).ready(function(){
                init();
                var c=1;
                $('#addArticle').click(function(){
                    var tr = document.createElement('tr');

                    var tdContent = Array(
                        '<textarea name="article['+c+'][Description]" placeholder="Artikel"></textarea>',
                        '<input type="text" name="article['+c+'][IdNumber]" placeholder="Artikelnummer">',
                        '<input type="number" name="article['+c+'][orderCount]" value="1">',
                        '<textarea name="article['+c+'][Memo]" placeholder="Bemerkungen"></textarea>'
                    );

                    for (var i = 0; i < 4; i++) {

                        var td = document.createElement('td');
                        td.innerHTML = tdContent[i];
                        tr.appendChild(td);

                    }
                    var tb = document.getElementById('articleList');
                    tb.appendChild(tr);

                    c++;
                })


            });

            var Base64 = {

                // private property
                _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

                // public method for encoding
                encode : function (input) {
                    var output = "";
                    var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
                    var i = 0;

                    input = Base64._utf8_encode(input);

                    while (i < input.length) {

                        chr1 = input.charCodeAt(i++);
                        chr2 = input.charCodeAt(i++);
                        chr3 = input.charCodeAt(i++);

                        enc1 = chr1 >> 2;
                        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                        enc4 = chr3 & 63;

                        if (isNaN(chr2)) {
                            enc3 = enc4 = 64;
                        } else if (isNaN(chr3)) {
                            enc4 = 64;
                        }

                        output = output +
                            this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
                            this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

                    }

                    return output;
                },

                // public method for decoding
                decode : function (input) {
                    var output = "";
                    var chr1, chr2, chr3;
                    var enc1, enc2, enc3, enc4;
                    var i = 0;

                    input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

                    while (i < input.length) {

                        enc1 = this._keyStr.indexOf(input.charAt(i++));
                        enc2 = this._keyStr.indexOf(input.charAt(i++));
                        enc3 = this._keyStr.indexOf(input.charAt(i++));
                        enc4 = this._keyStr.indexOf(input.charAt(i++));

                        chr1 = (enc1 << 2) | (enc2 >> 4);
                        chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
                        chr3 = ((enc3 & 3) << 6) | enc4;

                        output = output + String.fromCharCode(chr1);

                        if (enc3 != 64) {
                            output = output + String.fromCharCode(chr2);
                        }
                        if (enc4 != 64) {
                            output = output + String.fromCharCode(chr3);
                        }

                    }

                    output = Base64._utf8_decode(output);

                    return output;

                },

                // private method for UTF-8 encoding
                _utf8_encode : function (string) {
                    string = string.replace(/\r\n/g,"\n");
                    var utftext = "";

                    for (var n = 0; n < string.length; n++) {

                        var c = string.charCodeAt(n);

                        if (c < 128) {
                            utftext += String.fromCharCode(c);
                        }
                        else if((c > 127) && (c < 2048)) {
                            utftext += String.fromCharCode((c >> 6) | 192);
                            utftext += String.fromCharCode((c & 63) | 128);
                        }
                        else {
                            utftext += String.fromCharCode((c >> 12) | 224);
                            utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                            utftext += String.fromCharCode((c & 63) | 128);
                        }

                    }

                    return utftext;
                },

                // private method for UTF-8 decoding
                _utf8_decode : function (utftext) {
                    var string = "";
                    var i = 0;
                    var c = c1 = c2 = 0;

                    while ( i < utftext.length ) {

                        c = utftext.charCodeAt(i);

                        if (c < 128) {
                            string += String.fromCharCode(c);
                            i++;
                        }
                        else if((c > 191) && (c < 224)) {
                            c2 = utftext.charCodeAt(i+1);
                            string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                            i += 2;
                        }
                        else {
                            c2 = utftext.charCodeAt(i+1);
                            c3 = utftext.charCodeAt(i+2);
                            string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                            i += 3;
                        }

                    }

                    return string;
                }

            }

            function getData(){

                var mID = $( "#mID" ).val();
                $.ajax({
                    type: "GET",
                    url: "orders_manufacturers_direct.php?step=loadData&mID="+mID,
                    dataType: "text",
                    contentType: "application/json; charset=utf-8",
                    success: function(data) {
                        console.log(data);
                        obj = JSON.parse(data);
                        document.getElementById('#manufacturersTbody').innerHTML = Base64.decode(obj.emails);
                        $('#morder_cnumber').val(obj.customer_number);
                    }
                });

            }
        </script>
    </head>
    <body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="SetFocus();">
    <!-- header //-->
    <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
    <!-- header_eof //-->
    <div class="nav">
        <?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
    </div>
    <div class="adminContent">
        <?php

        //Hersteller laden
        $manufacturers_query = xtc_db_query("select `manufacturers_id`, `manufacturers_name` From `" . TABLE_MANUFACTURERS . "` order by `manufacturers_name`;");

        while ($manufacturers = xtc_db_fetch_array($manufacturers_query)){

            $options .= "<option value='".$manufacturers['manufacturers_id']."'>".$manufacturers['manufacturers_name']."</option>";

        }


        ?>
        <form method="post" name="morder" action="/admin/orders_manufacturers_direct.php?step=send#kb-module" enctype="multipart/form-data">
            <h3>Direkte Bestellung bei einem Hersteller / Lieferanten</h3>
            <div class="first_level_menu_container">
            // ... existing code ...
<ul class="tabset_tabs">
    <li>
        <a id="tablink1" class="tab1 tabactive" title="" onClick="easytabs('1', '1');" href="#">Hersteller</a>
    </li>
    <li>
        <a id="tablink2" class="tab2" title="" onClick="easytabs('1', '2');" href="#">Artikel</a>
    </li>
    <li>
        <a id="tablink3" class="tab3" title="" onClick="easytabs('1', '3');" href="#">Lieferanschrift</a>
    </li>
    <li>
        <a id="tablink4" class="tab4" title="" onClick="easytabs('1', '4');" href="#">Zusatzinformationen</a>
    </li>
    <li>
        <a id="tablink5" class="tab5" title="" onClick="easytabs('1', '5');" href="#">Datei Upload</a>
    </li>
</ul>
// ... existing code ...
            </div>
            <script type="text/javascript">
                /*
                EASY TABS 1.2 Produced and Copyright by Koller Juergen
                www.kollermedia.at | www.austria-media.at
                Need Help? http:/www.kollermedia.at/archive/2007/07/10/easy-tabs-12-now-with-autochange
                You can use this Script for private and commercial Projects, but just leave the two credit lines, thank you.
                */
                //EASY TABS 1.2 - MENU SETTINGS
                //Set the id names of your tablinks (without a number at the end)
                var tablink_idname = new Array("tablink");
                //Set the id names of your tabcontentareas (without a number at the end)
                var tabcontent_idname = new Array("tabcontent");
                //Set the number of your tabs in each menu
                var tabcount = new Array("5");
                //Set the Tabs wich should load at start (In this Example:Menu 1 -> Tab 2 visible on load, Menu 2 -> Tab 5 visible on load)
                var loadtabs = new Array("1");
                //Set the Number of the Menu which should autochange (if you dont't want to have a change menu set it to 0)
                var autochangemenu = 0;
                //the speed in seconds when the tabs should change
                var changespeed = 3;
                //should the autochange stop if the user hover over a tab from the autochangemenu? 0=no 1=yes
                var stoponhover = 0;
                //END MENU SETTINGS
                /*Swich EasyTabs Functions - no need to edit something here*/
                function easytabs(menunr, active) {if (menunr == autochangemenu){currenttab=active;}if ((menunr == autochangemenu)&&(stoponhover==1)) {stop_autochange()} else if ((menunr == autochangemenu)&&(stoponhover==0)) {counter=0;} menunr = menunr-1;for (i=1; i <= tabcount[menunr]; i++){document.getElementById(tablink_idname[menunr]+i).className='tab'+i;document.getElementById(tabcontent_idname[menunr]+i).style.display = 'none';}document.getElementById(tablink_idname[menunr]+active).className='tab'+active+' tabactive';document.getElementById(tabcontent_idname[menunr]+active).style.display = 'block';}var timer; counter=0; var totaltabs=tabcount[autochangemenu-1];var currenttab=loadtabs[autochangemenu-1];function start_autochange(){counter=counter+1;timer=setTimeout("start_autochange()",1000);if (counter == changespeed+1) {currenttab++;if (currenttab>totaltabs) {currenttab=1}easytabs(autochangemenu,currenttab);restart_autochange();}}function restart_autochange(){clearTimeout(timer);counter=0;start_autochange();}function stop_autochange(){clearTimeout(timer);counter=0;}
                function init(){
                    var menucount=loadtabs.length; var a = 0; var b = 1; do {easytabs(b, loadtabs[a]); a++; b++;}while (b<=menucount);
                    if (autochangemenu!=0){start_autochange();}
                }
            </script>
            <div class="detail_main">
                <div id="tabcontent1" class="tabset_content">
                    <table border="0" width="850" cellspacing="1" cellpadding="5" id="manufacturers">
                        <head>
                            <tr class="cframeHead">
                                <td align="left" colspan="4">Bitte Hersteller w&auml;hlen: <select name="mID" id="mID" onchange="javascript:getData();"><?php echo($options); ?></select></td>
                            </tr>
                        </head>
                        <tbody id="#manufacturersTbody">

                        </tbody>
                    </table>
                </div>
                <div id="tabcontent2" class="tabset_content">
                    <table border="0" width="850" cellspacing="1" cellpadding="5" id="articleList">
                        <tr class="cframeHead">
                            <td align="left" colspan="4">Bitte Artikel angeben:</td>
                        </tr>
                        <tr class="cframeLine">
                            <td  align="left"><b>Artikel</b></td>
                            <td align="left"><b>Artikelnummer</b></td>
                            <td align="left"><b>Anzahl</b></td>
                            <td  align="left">Bemerkungen</td>
                        </tr>
                        <tr>
                            <td class="cframeLine"><textarea name="article[0][Description]" placeholder="Artikel"></textarea></td>
                            <td class="cframeLine"><input type="text" name="article[0][IdNumber]" placeholder="Artikelnummer"></td>
                            <td class="cframeLine"><input type="number" name="article[0][orderCount]" value="1"></td>
                            <td class="cframeLine"><textarea name="article[0][Memo]" placeholder="Bemerkungen"></textarea></td>
                        </tr>
                    </table>
                    <input type="button" id="addArticle" class="button" value="+ Weiteren Artikel anf&uuml;gen"/></td>
                </div>
                <div id="tabcontent3" class="tabset_content">
                    <table width="850" cellspacing="1" cellpadding="4" border="0">
                        <tbody>
                        <tr class="cframeLine">
                            <td valign="top" align="left">Name:</td>
                            <td valign="top" align="left"><input type="text" value="" size="40" name="delivery_name" required="true"></td>
                        </tr>
                        <tr class="cframeLine">
                            <td valign="top" align="left">
                                Firma:
                            </td>
                            <td valign="top" align="left"><textarea name="delivery_company" rows="10" cols="40"></textarea></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div id="tabcontent4" class="tabset_content">
                    <table width="850" cellspacing="1" cellpadding="4" border="0">
                        <tbody>
                        <tr class="cframeLine">
                            <td valign="top" align="left">Ansprechpartner und Rufnummer:</td>
                            <td valign="top" align="left"><input type="text" value="" size="100" name="delivery_phone"  required="true"></td>
                        </tr>
                        <tr class="cframeLine">
                            <td valign="top" align="left">
                                Betreff (z.B. Kommissionsnummer):
                            </td>
                            <td valign="top" align="left"><input type="text" value="" size="40" name="morder_regard"></td>
                        </tr>
                        <tr class="cframeLine">
                            <td valign="top" align="left">
                                Kundennummer Hersteller :
                            </td>
                            <td valign="top" align="left"><input type="text" value="" size="40" name="morder_cnumber" id="morder_cnumber"   required="true" readonly=""></td>
                        </tr>
                        <tr class="cframeLine">
                            <td valign="top" align="left">
                                Bemerkung:
                            </td>
                            <td valign="top" align="left"><textarea cols="40" rows="4" name="morder_memo"></textarea></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div id="tabcontent4" class="tabset_content">
</div>
<div id="tabcontent5" class="tabset_content">
    <table width="850" cellspacing="1" cellpadding="4" border="0">
        <tbody>
        <tr class="cframeLine">
            <td valign="top" align="left">Datei auswählen:</td>
            <td valign="top" align="left">
                <input type="file" name="attachment" id="attachment">
            </td>
        </tr>
        </tbody>
    </table>
</div>
<input type="submit" value="Bestellmail senden" class="button" style="margin-top:10px;">
</div>
                <input type="submit" value="Bestellmail senden" class="button" style="margin-top:10px;">
            </div>
        </form>
    </div>

    <!-- footer //-->
    <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
    <!-- footer_eof //-->
    <br />
    </body>
    </html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); 
