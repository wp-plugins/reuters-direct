jQuery(document).ready(function()
{
    jQuery('.channels').show();

    if(jQuery('#category_checkboxes_Custom_Category').is(':checked'))
        jQuery('#add_category').show();
    else
        jQuery('#add_category').hide();

    jQuery("#help_links").on('change', function() {
        window.open( this.options[ this.selectedIndex ].value, '_blank');
        jQuery("#help_links").val("");
    });

    jQuery('label[for="category_checkboxes_SUBJ"]').hover(
        function(){
            jQuery("#SUBJ").show();  
        },
        function(){
            jQuery("#SUBJ").hide();  
        }
    );

    jQuery('label[for="category_checkboxes_N2"]').hover(
        function(){
            jQuery("#N2").show();  
        },
        function(){
            jQuery("#N2").hide();  
        }
    );

    jQuery('label[for="category_checkboxes_MCC"]').hover(
        function(){
            jQuery("#MCC").show();  
        },
        function(){
            jQuery("#MCC").hide();  
        }
    );

    jQuery('label[for="category_checkboxes_MCCL"]').hover(
        function(){
            jQuery("#MCCL").show();  
        },
        function(){
            jQuery("#MCCL").hide();  
        }
    ); 

    jQuery('label[for="category_checkboxes_RIC"]').hover(
        function(){
            jQuery("#RIC").show();  
        },
        function(){
            jQuery("#RIC").hide();  
        }
    );   

    jQuery('label[for="category_checkboxes_A1312"]').hover(
        function(){
            jQuery("#A1312").show();  
        },
        function(){
            jQuery("#A1312").hide();  
        }
    ); 

    jQuery('label[for="category_checkboxes_Agency_Labels"]').hover(
        function(){
            jQuery("#Agency_Labels").show();  
        },
        function(){
            jQuery("#Agency_Labels").hide();  
        }
    ); 

    jQuery('label[for="category_checkboxes_Custom_Category"]').hover(
        function(){
            jQuery("#Custom_Category").show();  
        },
        function(){
            jQuery("#Custom_Category").hide();  
        }
    );

    jQuery('#category_checkboxes_Custom_Category').change(function(){
        if(jQuery('#category_checkboxes_Custom_Category').is(':checked'))
            jQuery('#add_category').show();
        else
            jQuery('#add_category').hide();
            jQuery('#custom_category').val("");
    });  
});


function setFilter(category)
{
    jQuery('.category').removeClass('selected');
    jQuery('.channels').hide();
    if(category == 0)
    {
        jQuery('#ALL').addClass('selected');
        jQuery('.channels').show();
    }
    else if(category == 1)
    {
        jQuery('#OLR').addClass('selected');
        jQuery('#OLRChannels').show();
    }
    else if(category == 2)
    {
        jQuery('#TXT').addClass('selected');
        jQuery('#TXTChannels').show();
    }
    else if(category == 3)
    {
        jQuery('#PIC').addClass('selected');
        jQuery('#PICChannels').show();
    }
    else if(category == 4)
    {
        jQuery('#GRA').addClass('selected');
        jQuery('#GRAChannels').show();
    }
} 