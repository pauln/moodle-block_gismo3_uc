function left_menu(g) {
    // gismo instance
    this.gismo = g;
    
    // current visible list
    this.visible_list;
    
    // lists
    // this field is a javascript object that provides information for the supported lists of items such as icon & tooltip
    this.lists = {
        'users': {
            img: 'users.png', 
            tooltip: 'users'
        },
        'teachers': {
            img: 'teachers.png', 
            tooltip: 'teachers'
        },
        'resources': {
            img: 'resources.png', 
            tooltip: 'resources'
        }, 
        'assignments': {
            img: 'assignments.png', 
            tooltip: 'assignments'
        },
        'chats': {
            img: 'chat.gif', 
            tooltip: 'chats'
        }, 
        'forums': {
            img: 'forum.gif', 
            tooltip: 'forums'
        }, 
        'quizzes': {
            img: 'quizzes.png', 
            tooltip: 'quizzes'
        }, 
        'wikis': {
            img: 'wiki.gif', 
            tooltip: 'wikis'
        }
    };
    
    // list hidden on load
    // this field specify the lists that have to be hidden on load (icons in the header)
    this.lists_load_hidden = {
        'student': new Array('users', 'teachers'),
        'teacher': new Array()
    };
    
    // list visible on load
    // this field specify the list that has to be shown on load (list body)
    this.lists_load_default = {
        'student': 'resources',
        'teacher': 'users'
    };
    
    // list options
    // this field is a javascript object that provides information about lists for specific analysis
    this.list_options ={
        // students
        'teacher@student-resources-access': {
            'lists': ['users', 'resources'],
            'default': 0,
            'details': ['users']
        },
        'teacher@student-resources-access:users-details': {
            'lists': ['resources'],
            'default': 0,
            'details': []
        }, 
        'teacher@student-accesses': {
            'lists': ['users'],
            'default': 0,
            'details': []
        },
        'teacher@student-accesses-overview': {
            'lists': ['users'],
            'default': 0,
            'details': []
        },
        // resources
        'student@resources-students-overview': {
            'lists': ['resources'],
            'default': 0,
            'details': []
        }, 
        'teacher@resources-students-overview': {
            'lists': ['users', 'resources'],
            'default': 1,
            'details': []
        },  
        'teacher@resources-access': {
            'lists': ['users', 'resources'],
            'default': 1,
            'details': ['resources']
        },
        'student@resources-access': {
            'lists': ['resources'],
            'default': 0,
            'details': []
        },
        'teacher@resources-access:resources-details': {
            'lists': ['users'],
            'default': 0,
            'details': []
        }, 
        // activities -> assignments
        'teacher@assignments': {
            'lists': ['users', 'assignments'],
            'default': 0,
            'details': []
        },
        'student@assignments': {
            'lists': ['assignments'],
            'default': 0,
            'details': []
        },
        // activities -> chats
        'teacher@chats': {
            'lists': ['users', 'chats'],
            'default': 0,
            'details': ['users']
        },
        'teacher@chats-over-time': {
            'lists': ['users', 'chats'],
            'default': 0,
            'details': []
        },
        'teacher@chats:users-details': {
            'lists': ['chats'],
            'default': 0,
            'details': []
        },
        'student@chats': {
            'lists': ['chats'],
            'default': 0,
            'details': []
        },
        'student@chats-over-time': {
            'lists': ['chats'],
            'default': 0,
            'details': []
        },
        // activities -> forums
        'teacher@forums': {
            'lists': ['users', 'forums'],
            'default': 0,
            'details': ['users']
        },
        'teacher@forums-over-time': {
            'lists': ['users', 'forums'],
            'default': 0,
            'details': []
        },
        'teacher@forums:users-details': {
            'lists': ['forums'],
            'default': 0,
            'details': []
        },
        'student@forums': {
            'lists': ['forums'],
            'default': 0,
            'details': []
        },
        'student@forums-over-time': {
            'lists': ['forums'],
            'default': 0,
            'details': []
        },
        // activities -> quizzes
        'teacher@quizzes': {
            'lists': ['users', 'quizzes'],
            'default': 0,
            'details': []
        },
        'student@quizzes': {
            'lists': ['quizzes'],
            'default': 0,
            'details': []
        },
        // activities -> wikis
        'teacher@wikis': {
            'lists': ['users', 'wikis'],
            'default': 0,
            'details': ['users']
        },
        'teacher@wikis-over-time': {
            'lists': ['users', 'wikis'],
            'default': 0,
            'details': []
        },
        'teacher@wikis:users-details': {
            'lists': ['wikis'],
            'default': 0,
            'details': []
        },
        'student@wikis': {
            'lists': ['wikis'],
            'default': 0,
            'details': []
        },
        'student@wikis-over-time': {
            'lists': ['wikis'],
            'default': 0,
            'details': []
        }
    };
    
    // lists methods
    this.get_lists = function() {
        var result = new Array();
        if (this.gismo.util.get_assoc_array_length(this.lists) > 0) {
            for (var k in this.lists) {
                result.push(k);
            }
        }
        return result;
    };
    this.get_lists_by_current_analysis = function () {
        var full_type = this.gismo.get_full_type();
        var result = new Array();
        if (this.list_options[full_type] != undefined) {
            result = this.list_options[full_type]['lists'];
        }
        return result;
    };
    this.get_list_default = function () {
        var full_type = this.gismo.get_full_type();
        var result = 0;
        if (this.list_options[full_type] != undefined) {
            var available_lists = this.get_lists_by_current_analysis();
            if ($.isArray(available_lists) && available_lists[this.list_options[full_type]["default"]] != undefined) {
                result = available_lists[this.list_options[full_type]["default"]];
            }
        }
        return result;
    };
    this.get_list_details = function () {
        var full_type = this.gismo.get_full_type();
        var result = new Array();
        if (this.list_options[full_type] != undefined) {
            result = this.list_options[full_type]['details'];
        }
        return result;
    };
    
    // init lm header method
    this.init_lm_header = function() {
        // local variables
        var item, lm = this;
        // build header
        for (item in this.lists) {
            // add only if not empty
            if (this.gismo.static_data[item].length > 0) {
                $('#' + this.gismo.lm_header_id).append(
                    $('<a></a>')
                        .addClass("list_selector")
                        .attr({"href": "javascript:void(0);", "id": item + "_menu"})
                        .click(
                            {list: item, lm: this},
                            function (event) {
                                event.data.lm.show_list(event.data.list);
                                $(this).blur();
                            }
                        )
                        .append(
                            $('<img></img>')
                                .attr({
                                    "src": "images/" + this.lists[item]["img"],
                                    "alt": "Show " + this.lists[item]["tooltip"] + " list",
                                    "title": "Show " + this.lists[item]["tooltip"] + " list" 
                                })
                        )
                        .css(
                            "display", 
                            (this.lists_load_hidden[this.gismo.actor] != undefined && 
                            $.isArray(this.lists_load_hidden[this.gismo.actor]) && 
                            $.inArray(item, this.lists_load_hidden[this.gismo.actor]) != -1) ? "none" : "inline"
                        )
                );
            }
        }
    };
    
    // unique identifier
    // this function return an identifier for the item
    this.get_unique_id = function(item_type, item, id_field, type_field) {
        var result = false;
        if (id_field != undefined && item[id_field] != undefined) {
            result = (type_field != undefined && item[type_field] != undefined) ? item[type_field] : item_type;
            result += "-" + item[id_field];
        }
        return result;
        /*
        // defaults
        id_field = (id_field == undefined) ? "id" : id_field;
        type_field = (type_field == undefined) ? "type" : type_field;
        // build result
        var result = (item[type_field] != undefined) ? item[type_field] : item_type;
        result += "-" + item[id_field];
        return result;
        */
    }
    
    // init lm content method
    this.init_lm_content = function() {
        // local variables
        var element, cb_item, cb_label, item;
        var lm = this;
        var count;
        // create lists
        for (item in this.lists) {
            count = this.gismo.get_items_number(item);
            // list
            element = $('<div></div>').attr('id', this.get_list_container_id(item));
            if (count > 0) {
                var lab = (item == 'users') ? "students" : item;    // WORKAROUND
                // add header with a checkbox to control items selection
                element.append(
                    $('<div></div>').addClass("cb_main").append(
                        $("<label></label>")
                            .addClass("cb_label")
                            .html("<b>" + lab.toUpperCase() + " (" + count + " ITEMS)</b>")
                            .prepend(
                                $('<input></input>').addClass("cb_element")
                                    .attr({
                                        "type": "checkbox",
                                        "value": "0",
                                        "name": item + "_cb_control",
                                        "id": item + "_cb_control"
                                    })
                                    .prop("checked", true)
                                    .click(
                                        {list: item},
                                        function(event) {
                                            $('#' + event.data.list + '_list input:checkbox').prop('checked', $(this).prop('checked'));
                                            if (lm.gismo.current_analysis.plot != null && 
                                                lm.gismo.current_analysis.plot != undefined) {
                                                lm.gismo.update_chart();
                                            }
                                        }
                                    )
                            )
                    )
                );
                // add items checkboxes
                for (var k=0; k<this.gismo.static_data[item].length; k++) {
                    if (this.gismo.is_item_visible(this.gismo.static_data[item][k])) {
                        cb_item = $('<input></input>').attr("type", "checkbox");
                        // cb_item.attr("value", this.gismo.static_data[item][k].id);
                        cb_item.attr("value", this.get_unique_id(item, this.gismo.static_data[item][k], "id", "type"));
                        cb_item.attr("name", item + "_cb[" + this.gismo.static_data[item][k].id + "]");
                        cb_item.attr("id", item + "_cb_" + this.gismo.static_data[item][k].id);
                        cb_item.prop("checked", true);
                        cb_item.addClass("cb_element");
                        cb_item.bind("click", {list: item}, function (event) {
                            // if alt key has been pressed then this is the only one selected
                            if (event.altKey) {
                                $('#' + event.data.list + '_list input:checkbox').prop('checked', false);
                                $(this).prop('checked', true);
                            }
                            // manage global cb
                            var selector = '#' + event.data.list + '_list input[id!=' + event.data.list + '_cb_control]:checkbox';
                            var global_checked = ($(selector).length === $(selector + ":checked").length) ? true : false;
                            $('input#' + event.data.list + '_cb_control').prop('checked', global_checked);
                            // update chart
                            if (lm.gismo.current_analysis.plot != null && lm.gismo.current_analysis.plot != undefined) {
                                lm.gismo.update_chart();
                            }
                        });
                        cb_label = $("<label style='float: left;'></label>")
                                        .html(this.gismo.static_data[item][k].name)
                                        .mouseover(function () {
                                            $(this).addClass("selected");
                                        })
                                        .mouseout(function () {
                                            $(this).removeClass("selected");
                                        });
                        cb_label.addClass("cb_label");
                        cb_label.prepend(cb_item);
                        element.append(
                            $("<div></div>").addClass("cb")
                            .append(cb_label)
                            .append(
                                $("<image style='float: left; margin-top: 3px; margin-left: 5px;'></image>")
                                .attr("id", item + "_" + this.gismo.static_data[item][k].id)
                                .attr({src: "images/eye.png", title: "Details"})
                                .addClass(item + "_details image_link float_right")
                                .mouseover(function () {
                                    $(this).parent().addClass("selected");
                                })
                                .mouseout(function () {
                                    $(this).parent().removeClass("selected");
                                })
                                .click(function () {
                                    var options = $(this).attr("id").split("_");
                                    g.analyse(g.current_analysis.type, {subtype: options[0] + "-details", id: options[1]});
                                })
                            )
                        );
                    }
                }
            } else {
                element.html("<p>There isn't any " + item + " in the course!</p>");
            }
            element.hide();
            $('#' + this.gismo.lm_content_id).append(element);
        }
        $('#' + this.gismo.lm_content_id).append($('<br style="clear: both;" />'));
        $('#' + this.gismo.lm_content_id).append($('<div></div>').css({"height": "10px"}))  
    };
    
    this.init_lm_content_details = function() {
        // hide all details controls
        var selectors = new Array(), lists = this.get_lists(), k;
        for (k=0;k<lists.length;k++) {
            selectors.push("." + lists[k] + "_details");
        }
        $(selectors.join(", ")).hide();
        // show detais for current analysis
        var details = this.get_list_details();
        for (k in details) {
            $("." + details[k] + "_details").show();
        }
    };

    // clean
    this.clean = function () {
        // clean header
        $('#' + this.gismo.lm_header_id + " .list_selector").remove();
        // clean content
        $('#' + this.gismo.lm_content_id).empty();
    };
    
    // init method
    this.init = function () {
        // clean
        this.clean();
        // set default visible list
        this.visible_list = "resources";
        if (this.lists_load_default[this.gismo.actor] != undefined &&
            $.inArray(this.lists_load_default[this.gismo.actor], this.get_lists()) != -1) {
            this.visible_list = this.lists_load_default[this.gismo.actor];
        }
        // init header (link icons)
        this.init_lm_header();
        // init content (build lists)
        this.init_lm_content();
        // show / hide items details
        this.init_lm_content_details();
        // show current list
        this.show_list(this.visible_list);
    };
    
    this.get_list_container_id = function (list) {
        return list + "_list";    
    };
    
    this.show_list = function (list) {
        // hide previous list
        $("#" + this.get_list_container_id(this.visible_list)).hide();
        // show new list
        $("#" + this.get_list_container_id(list)).show();
        // update current list
        this.visible_list = list;
    };
    
    this.get_selected_items = function () {
        var selected_items = new Array();
        for (var item in this.lists) {
            selected_items[item] = new Array();
            $("#" + this.get_list_container_id(item) + " input:checkbox:checked").each(function (index) {
                selected_items[item].push($(this).val());            
            });    
        }
        return selected_items;            
    };
   
    this.set_menu = function (fresh) {
        // all available lists
        var all = this.get_lists();
        // enabled lists (according to current analysis)
        var enabled = this.get_lists_by_current_analysis();
        // visible list (according to current analysis)
        var visible = this.get_list_default();
        // keep visible list ?
        if (fresh == false && $.inArray(this.visible_list, enabled) !== -1) {
            visible = this.visible_list;
        }
        // set lists visibility (icons in the header)
        for (var item in all) {
            if ($.inArray(all[item], enabled) !== -1) {
                $("#" + all[item] + "_menu").show();     
            } else {
                $("#" + all[item] + "_menu").hide();
            }
        }
        // show correct list (list content)
        this.show_list(visible);
    };
    
    this.show = function() {
        $('#open_control').hide(); 
        $('#close_control').show(); 
        $('#left_menu').show();
        $('#left_menu').toggleClass('closed_lm'); 
        $('#chart').toggleClass('expanded_ch');
        if (this.gismo.get_full_type() != null) {
            this.gismo.update_chart();   
        }   
    };
    
    this.hide = function() {
        $('#open_control').show(); 
        $('#close_control').hide(); 
        $('#left_menu').hide();
        $('#chart').toggleClass('expanded_ch');
        $('#left_menu').toggleClass('closed_lm'); 
        if (this.gismo.get_full_type() != null) {
            this.gismo.update_chart();   
        }
    };

    // info
    this.show_info = function() {
        var title = "GISMO - Lists";
        var message = "<p>To customize the chart you can select/unselect items from enabled menus.</p>";
        message += "<p>Instructions</p>";
        message += "<ul style='list-style-position: inside;'>";
        message += "<li>Main Checkbox: select/unselect all list items.</li>";
        message += "<li>Item Click: select/unselect the clicked item.</li>";
        message += "<li>Item Alt+Click: select only the clicked item</li>";
        message += "<li><img src='images/eye.png'> show item details</li>";
        message += "</ul>";
        this.gismo.util.show_modal_dialog(title, message);
    };
}