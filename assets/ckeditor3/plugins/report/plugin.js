(function() {

    CKEDITOR.plugins.add('report', {
        init: function(editor) {
            editor.addCommand('report', o);
                    editor.ui.addButton('report', {
                        label: 'report',
                        icon: this.path + 'images/report.jpg',
                        command: 'report'
                    });
            editor.ui.addButton( 'report',
                {
                    label: 'Vložit filter',
                    command: 'reportDialog',
                    icon: this.path + 'images/report.jpg'
                });
            editor.addCommand( 'reportDialog', new CKEDITOR.dialogCommand( 'reportDialog' ));
        }
    });

    CKEDITOR.dialog.add( 'reportDialog', function( editor )
    {

        /** texts  */
        var texts = getArray('texts');
        var textFilter = getFilter('texts');
        /** sentences */
        var sentences = getArray('sentences');
        var sentenceFilter = getFilter('sentences');
        /** words */
        var words = getArray('words');
        var wordFilter = getFilter('words');
        /** offset */
        var offsets = getArray('offset');
        var offsetFilter = getFilter('offset');
        /** inflection */
        var inflection = getArray('inflection');
        var inflectList = getFilter('inflection');

        return {

            title : 'Vložení filtru',
            minWidth : 400,
            minHeight : 200,
            contents :
                [
                    {
                        id : 'general',
                        label : 'Settings',
                        elements :
                            [
                                {
                                    type : 'html',
                                    html : 'Zvolte filter.'
                                },
                                {
                                    type : 'select',
                                    id : 'texts',
                                    label : 'Filtrovat text',
                                    items : texts,
                                    commit : function( data )
                                    {
                                        data.texts = this.getValue();
                                    }
                                },
                                {
                                    type : 'select',
                                    id : 'sentences',
                                    label : 'Filtrovat větu',
                                    items : sentences,
                                    commit : function( data )
                                    {
                                        data.sentences = this.getValue();
                                    }
                                },
                                {
                                    type : 'select',
                                    id : 'words',
                                    label : 'Filtrovat slovo',
                                    items : words,
                                    commit : function( data )
                                    {
                                        data.words = this.getValue();
                                    }
                                },
                                {
                                    type : 'select',
                                    id : 'offsets',
                                    label : 'Filtrovat pořadí',
                                    items : offsets,
                                    commit : function( data )
                                    {
                                        data.offsets = this.getValue();
                                    }
                                },
                                {
                                    type : 'text',
                                    id : 'keywords',
                                    label : 'Filtrovat klíčové slovo',
                                    items : 'Napište jakékoliv klíčové slovo.',
                                    commit : function( data )
                                    {
                                        data.keywords = this.getValue();
                                    }
                                },
                                {
                                    type : 'select',
                                    id : 'inflection',
                                    label : 'Gramatická kategorie',
                                    items : inflection,
                                    commit : function( data )
                                    {
                                        data.inflection = this.getValue();
                                    }
                                }
                            ]
                    }
                ],
            onOk : function()
            {
                /** data */
                var data = {};
                this.commitContent(data);
                /** texts */
                if('' !== data.texts) {
                    var html = addSelectBox(textFilter, data.texts, 'background-color: orange;');
                    editor.insertHtml(html);
                }
                /** sentences */
                if('' !== data.sentences) {
                    var html = addSelectBox(sentenceFilter, data.sentences, 'background-color: lime;');
                    editor.insertHtml(html);
                }
                /** words */
                if('' !== data.words) {
                    var html = addSelectBox(wordFilter, data.words, '');
                    editor.insertHtml(html);
                }
                /** keywords */
                if('' !== data.keywords) {
                    var html = '<select style="width: 150px;"><option selected="selected" value="' + data.keywords + '.Keyword">' + data.keywords + '</option></select>';
                    editor.insertHtml(html);
                }
                /** inflection */
                if('' !== data.inflection) {
                    var html = addSelectBox(inflectList, data.inflection);
                    editor.insertHtml(html);
                }
                /** offset */
                if('' !== data.offsets) {
                    var html = addSelectBox(offsetFilter, data.offsets);
                    editor.insertHtml(html);
                }
                console.log('Report wysiwig done.');
            }
        };
    });

    $(document).on("click", ".cke_off.cke_button_reportDialog", function(){

    });

    function getArray(type) {
        var ul = 'ul.' + type + ' li';
        var array = [];
        $(ul).each(function()
        {
            var item = [];
            item.push($(this).html());
            array.push(item);
        });
        return array;
    }

    function getFilter(type) {
        var ul = 'ul.' + type + ' li';
        var array = [];
        $(ul).each(function()
        {
            var value = $(this).html();
            var key = $(this).attr('class');
            array.push({key : key, value : value});
        });

        return array;
    }

    function addSelectBox(filterList, selected, color) {

        var selectBox = '<select style="width: 150px;' + color + '">';
        $.each(filterList, function(key, item)
        {
            console.log(item.value);
            selectBox += "<option ";
            if(item.value == selected) {
                selectBox += 'selected="selected" ';
            }
            selectBox += "value='" + item.key + "'>" + item.value + "</option>";
        });
        selectBox += '</select>';
        return selectBox;
    }

    /** do not remove this */
    var o = { exec: function(editor) {
            console.log('report done');
        }
    };

})();
