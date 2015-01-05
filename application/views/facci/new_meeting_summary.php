<!-- Maestrano Facci Customisation -->
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <script type="text/javascript" src="<?php echo Yii::app()->getConfig('generalscripts');?>jquery/jquery.js"></script>
    <script type="text/javascript" src="<?php echo Yii::app()->getConfig('generalscripts');?>jquery/jquery-ui.js"></script>
    <script type="text/javascript" src="<?php echo Yii::app()->getConfig('generalscripts');?>jquery/jquery.ui.touch-punch.min.js"></script>
    <script type="text/javascript" src="<?php echo Yii::app()->getConfig('generalscripts');?>jquery/jquery.qtip.js"></script>
    <script type="text/javascript" src="<?php echo Yii::app()->getConfig('generalscripts');?>jquery/jquery.notify.js"></script>
    <script type="text/javascript" src="<?php echo Yii::app()->getConfig('generalscripts');?>jquery/jquery-ui-timepicker-addon.js"></script>
    <script type="text/javascript" src="<?php echo Yii::app()->getConfig('adminscripts');?>admin_core.js"></script>

    <title>Facci - Meeting Summary</title>

    <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->getConfig('adminstyleurl');?>jquery-ui/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->getConfig('adminstyleurl');?>printablestyle.css" media="print" />
    <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->getConfig('adminstyleurl');?>adminstyle.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->getConfig('styleurl');?>adminstyle.css" />
    <link rel="shortcut icon" href="http://www.facci.com.au/fileadmin/template/australie/favicon.ico" type="image/x-ico; charset=binary">

    <style>
      .facci-top {
        background-color: #1d5c85;
        font-size: 1.4em;
        height: 38px;
      }

      .facci-section {
        font-size: 16px;
        margin-top: 20px;
      }
    </style>
  </head>
  
  <body>
    <?php if(isset($flashmessage)) { ?>
        <div id="flashmessage" style="display:none;">
            <div id="themeroller" class="ui-state-highlight ui-corner-all">
                <!-- close link -->
                <a class="ui-notify-close" href="#">
                    <span class="ui-icon ui-icon-close" style="float:right">&nbsp;</span>
                </a>
                <!-- alert icon -->
                <span style="float:left; margin:2px 5px 0 0;" class="ui-icon ui-icon-info">&nbsp;</span>
                <p><?php echo $flashmessage; ?></p><br>
            </div>
            <!-- other templates here, maybe.. -->
        </div>
    <?php } ?>

    <div class="facci-top"></div>

    <?php echo CHtml::form(array("facci/save"), 'post', array('class'=>'form30', 'id'=>'facciform')); ?>
      <div class='header ui-widget-header facci-section'>Meeting with customers and prospects</div>
      <div id="meeting-detail-section" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
        <ul>

          <li>
            <label for='meeting_date'>Date of the Meeting</label>
            <input class='datetimepicker' type='text' size='10' id='meeting_date' name='meeting_date' required="required" placeholder="Meeting date" />
            <font color='red' face='verdana' size='1'> Required</font>
          </li>

          <li>
            <label for='customer_type'>Did you meet a prospect or an existing FACCI customer?</label>
            <table>
              <tr><td><input type="radio" name="customer_type" value="Prospect">Prospect - Not a FACCI customer</input></td></tr>
              <tr><td><input type="radio" name="customer_type" value="Partner">Partner of FACCI - Not a customer</input></td></tr>
              <tr><td><input type="radio" name="customer_type" value="Customer">Existing FACCI customer</input></td></tr>
            </table>
          </li>

          <li>
            <label for='organization'>Name of the organisation you met</label>
            <select name='organization' id='organization' style="width: 255px;">
              <option value="">Select Organization</option>
              <?php
                foreach ($organizations as $organization) {
                  echo '<option value="' . $organization->mno_uid . '">' . $organization->title . '</option>';
                }
              ?>
            </select>
          </li>
          <li>
            <label for='new_organization'>Or create a new Organisation</label>
            <input type='text' id='new_organization' name='new_organization' size="40" placeholder="Organisation name" />
            <br/><br/>
          </li>

          <li>
            <label for='person'>Who did you meet in this organisation?</label>
            <select name='person' id='person' style="width: 255px;">
              <option value="">Select Person</option>
            </select>
          </li>
          <li>
            <label for='new_person_title'>Or create a new Contact</label>
            <input type='text' size="10" id='new_person_title' name='new_person_title' placeholder="Title" />
            <input type='text' id='new_person_first_name' name='new_person_first_name' placeholder="First name" />
            <input type='text' id='new_person_last_name' name='new_person_last_name' placeholder="Last name" />
          </li>

        </ul>
      </div>

      <div class='header ui-widget-header facci-section'>
        Discussion subject(s) / topic(s)
        <button type="button" id="add-topic-link" class="limebutton ui-state-default ui-corner-all">Add Topic</button>
      </div>

      <div id="topics-section" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
        <ul>
          <li>
            <label for='description'>Meeting summary</label>
            <textarea id='description' name='description' style="height: 77px; width: 727px;"></textarea>
          </li>
        </ul>
      </div>

      <div class='header ui-widget-header facci-section'>
        Next steps and actions
        <button type="button" id="add-action-link" class="limebutton ui-state-default ui-corner-all">Add Action</button>
      </div>

      <div id="actions-sections" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
        <div style="padding: 5px; margin-left: 17px; clear: both; font-weight: bold; float: left;">
          <div style="width: 203px; float: left;">Action type</div>
          <div style="width: 313px; float: left;">Action description</div>
          <div style="width: 203px; float: left;">Action assigned to</div>
          <div style="width: 100px; float: left;">Due date</div>
        </div>
      </div>

      <p>
        <input type='submit' value='Save meeting summary' />
      </p>

    </form>

    <script type="text/javascript">
      var organizations = {
        <?php
          foreach ($organizations as $organization) {
            $organization_name = str_replace(array("\r", "\n"), "", $organization->title);
            echo "\"$organization->mno_uid\": {\"name\": \"$organization_name\", \"code\": \"$organization->code\"},";
          }
        ?>
      };

      var persons = {
        <?php
          foreach ($persons as $person) {
            $person_name = str_replace(array("\r", "\n"), "", $person->title); 
            echo "\"$person->code\": {\"name\": \"$person_name\", \"mnoid\": \"$person->mno_uid\"},";
          }
        ?>
      };

      function updatePersonsList() {
        $('#person').empty();
        $('#person').append($('<option>').text('Select Person').attr('value', ''));

        var selectedOrg = $("#organization option:selected").val();
        if(selectedOrg != '') {
          var selectedOrganizationCode = organizations[selectedOrg]['code'];
          
          $.each(persons, function(code, value) {
            if(code.indexOf(selectedOrganizationCode) == 0) {
              $('#person').append($('<option>').text(value['name']).attr('value', value['mnoid']));
            }
          });
        }
      }

      function initializeTopicsForm() {
        var max_fields = 10;
        var wrapper    = $("#topics-section li:eq(0)");
        var add_button = $("#add-topic-link");
      
        var x = 0;
        $(add_button).click(function(e) {
            e.preventDefault();
            if(x < max_fields) {
                x++;
                $(wrapper).before('<li> \
                  <label for="topic' + x + '">Subject ' + x + '</label> \
                  <input type="text" size="120" id="topic' + x + '" name="topic' + x + '" /> \
                </li>');
            }
        });

        // Add the first topic form line
        $(add_button).trigger('click');
      }

      function initializeActionsForm() {
        var max_fields = 10;
        var wrapper    = $("#actions-sections");
        var add_button = $("#add-action-link");
      
        var x = 0;
        $(add_button).click(function(e) {
            e.preventDefault();
            if(x < max_fields) {
                x++;
                $(wrapper).append('<div style="margin: 5px; padding: 5px; margin-left: 10px; clear: both; border: 1px solid #B0B0B0;"> \
                    <div> \
                      <select name="actions[]" style="width: 200px;"> \
                        <option value="">Select action type</option> \
                        <option value="Follow up discussion">Follow up discussion</option> \
                        <option value="Send documents">Send documents</option> \
                        <option value="Send proposal">Send proposal</option> \
                        <option value="Invite to event">Invite to event</option> \
                      </select> \
                      <input type="text" name="action_descriptions[]" placeholder="Action description" style="width: 300px;"/> \
                      <select name="action_assignees[]" style="width: 200px;"> \
                        <?php
                          foreach ($users as $user) {
                            echo "<option value=" . $user->mno_uid . ">" . $user->full_name . "</option>";
                          }
                        ?>
                      </select> \
                      <input class="datetimepicker" type="text" size="10" name="action_due_dates[]" placeholder="Due date" /> \
                      <button type="button" class="remove_field limebutton ui-state-default ui-corner-all">Remove Action</button> \
                    </div> \
                    <div> \
                      <input type="text" name="action_others[]" placeholder="Other action type" style="width: 190px;"/> \
                    </div> \
                  </div>');
            }

            $(".datetimepicker:last").datetimepicker({showTimepicker: false, showTime: false, dateFormat: "mm/dd/yy"});

            $(".remove_field:last").click(function(e) {
              e.preventDefault();
              $(this).parent('div').parent('div').remove(); x--;
            });
        });

        // Add the first action form line
        $(add_button).trigger('click');
      }

      // Initialize JS widgets
      $(document).ready(function() {
        initializeActionsForm();
        initializeTopicsForm();

        $("#organization").change(function(e) {
          updatePersonsList();
        });

        $(".datetimepicker").datetimepicker({showTimepicker: false, showTime: false, dateFormat: "mm/dd/yy"});
      });
    </script>
  </body>
</html>
