import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import Form from './Form.jsx'

var ACTIONS = 'actions'
var BUTTONS = 'buttons'
var COLUMNS = 'columns'
var DIALOGS = 'dialogs'
var ROWS = 'rows'

export default class Grid extends Form {
    constructor(props) {
        super(props)
        var name = this.constructor.name;
        this.state = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data'));
    }
    addAction(key) {
        if(false == jQuery.isEmptyObject(this.state[BUTTONS][key])) {
            if(undefined == this.state[BUTTONS][key].style) {
                var style = {marginRight: '10px'}
            } else {
                var style = this.state[BUTTONS][key].style
            }
            return <a className={this.state[BUTTONS][key].class}
                      id={key}
                      href={this.state[BUTTONS][key].href}
                      key={key}
                      style={style}
                      onClick={this.bind(this.state[BUTTONS][key].onClick)}>{this.state[BUTTONS][key].label}</a>
        }
    }
    addActions(row, key) {
        var container = []
        for(var dialog in this.state[BUTTONS].dialogs) {
            container.push(this.addDialog(dialog, key, row))
        }
        for(var action in this.state[ACTIONS]) {
            var id = 'action-' + key + '-' + action
            var href = this.state[ACTIONS][action]['href']
            if(undefined == this.state[ACTIONS][action].onClick) {
                href += '?'
            } else {
                href += '&'
            }
            for(var parameterId in this.state[ACTIONS][action].parameters) {
                    href += parameterId + '=' + row[this.state[ACTIONS][action].parameters[parameterId]] + '&'
            }
            if(this.state[ACTIONS][action].url.length > 0) {
                href = row[this.state[ACTIONS][action].url]
            }
            container.push(<div key={id} className='fa-hover col-md-1'><a
                  className={this.state[ACTIONS][action].class}
                  href={href}
                  id={key}
                  onClick={this.bind(this.state[ACTIONS][action].onClick)}
                  target='_blank'
                  title={this.state[ACTIONS][action].label}></a></div>)
        }
        return container
    }
    addBody() {
        var body = []
        var rows = this.state[ROWS]
        var i = 0;
        body.push(this.addSummary())
        for(var key in rows) {
            var id = 'row-' + i++;
            body.push(<tr id={id} style={rows[key].style} key={id}>{this.addRow(rows[key], key)}</tr>)
        }
        return body
    }
    addColumns() {
        var body = [];
        var columns = this.state[COLUMNS]
        for (var key in columns) {
            var closure = this[columns[key].Method]
            if('function' == typeof(closure) && false == columns[key].Attributes.filter) {
                body.push(this[columns[key].Method](key))
            }
        }
        return body;
    }
    addDialog(dialog, key, data) {
        var id = 'dialog-' + dialog + '-' + key
        return <div key={id} className='fa-hover col-md-1'><a
            className={'fa-hover fa fa-' + dialog}
            data-target={'#masala-' + dialog}
            data-toggle='modal'
            onClick={this.bind(dialog, key)}
            title={dialog}></a></div>
    }
    addSettings() {
        var settings = []
        var columns = this.state[COLUMNS]
        for (var key in columns) {
            if(null == /\s/.exec(key) && 'groups' != key) {
                var id = key + '-setting'
                var checked = 'checked'
                if(true == columns[key].Attributes.unrender) { checked = null }
                settings.push(<div key={id} style={{float:'left'}}>
                    <input checked={checked} id={key} onClick={this.setting.bind(this)} type='checkbox' />&nbsp;&nbsp;{columns[key].Label}&nbsp;&nbsp;
                </div>)
            }
        }
        return <div style={{display:this.state[BUTTONS].setting.display}}>{settings}</div>
    }
    addEmpty(key) {
        var th = 'grid-col-' + key
        return <th key={key} className={th}></th>
    }
    addFilters() {
        var body = [];
        var columns = this.state[COLUMNS]
        for (var key in columns) {
            var closure = this[columns[key].Method]
            if('function' == typeof(closure) && true == columns[key].Attributes.filter) {
                body.push(this[columns[key].Method](key))
            }
        }
        return body;
    }
    addHidden(key) {}
    addMultiSelect(key) {
        var th = 'grid-col-' + key
        var columns = this.state[COLUMNS]
        return <th className={th} key={key}>
            {this.addLabel(key)}
            <select className={columns[key].Attributes.class}
                    defaultValue={columns[key].Attributes.value}
                    id={columns[key].Attributes.id}
                    multiple style={columns[key].Attributes.style}
                    onChange={this.change.bind(this, key)}
                >{this.getOptions(key)}>
            </select></th>
    }
    addLabel(key) {
        if(true == this.state[COLUMNS][key].Attributes.unfilter || true == this.state[COLUMNS][key].Attributes.filter) {
            return <label>{this.state[COLUMNS][key].Label}</label>
        }
    }
    addLabels() {
        var labels = []
        var columns = this.state[COLUMNS]
        for (var key in columns) {
            if(true != columns[key].Attributes.unrender) {
                var id = key + '-sort'
                if(undefined != columns[key].Attributes.order) {
                    var order = 'fa fa-sort-' + columns[key].Attributes.order
                } else {
                    var order = 'fa fa-sort'
                }
                labels.push(
                    <th key={id} onClick={this.sort.bind(this, key)}>
                        <a id={id} className='fa-hover' href='javascript:;' title='Sort ascending'>
                            <div className='fa-hover sort'>
                                {columns[key].Label}
                                <i className={order} aria-hidden='true'></i>
                            </div></a>
                    </th>)
            }
        }
        return labels;
    }
    addPaginator() {
        var container = []
        var extent = 9
        if(this.state[BUTTONS]['page'] > 1) {
            container.push(<li key='previous-page'><a aria-label='Previous' onClick={this.setPage.bind(this, this.state[BUTTONS]['page'] - 1)}><span aria-hidden="true">&laquo;</span></a></li>)
        }
        var i = 0
        while(i < extent) {
            container = this.getPage(container, i++)
        }
        if(this.state[BUTTONS]['pages'] > i) {
            container.push(<li key='next-page'><a aria-label='Next' onClick={this.setPage.bind(this, this.state[BUTTONS]['page'] + i)}><span aria-hidden="true">&raquo;</span></a></li>)
        }
        return container
    }
    addProgressBar(key){
        var id = key + '-progress'
        if(false == jQuery.isEmptyObject(this.state[BUTTONS][key])) {
            return <div key={id} className='progress'><div className='progress-bar' style={{width:this.state[BUTTONS][key].width+'%'}}></div></div>
        }
    }
    addRow(rows, key) {
        var container = []
        for(var row in rows) {
            if(undefined != this.state[COLUMNS][row] && true != this.state[COLUMNS][row].Attributes.unrender) {
                var td = 'grid-col-' + key
                var id = 'grid-col-' + key + row
                if('object' == typeof(rows[row]) && null !== rows[row]) {
                    rows[row].Attributes.id = row
                    rows[row].Attributes.onChange = this.update.bind(this, key, row)
                    container.push(<td key={id} className={td}>{React.createElement(rows[row].Tag, rows[row].Attributes, rows[row].Label)}</td>)
                } else {
                    container.push(<td key={id} className={td}>{rows[row]}</td>)
                }
            }
        }
        var id = 'grid-col-' + key + '-actions'
        container.push(<td key={id}>{this.addActions(rows, key)}</td>)
        return container
    }
    addSelect(key) {
        var th = 'grid-col-' + key
        var columns = this.state[COLUMNS]
        return <th className={th} key={key}>
                {this.addLabel(key)}
                <select className={columns[key].Attributes.class}
                        defaultValue={columns[key].Attributes.value}
                        id={columns[key].Attributes.id}
                        style={columns[key].Attributes.style}
                        onChange={this.change.bind(this, key)}
                        >{this.getOptions(key)}>
                </select></th>
    }
    addSummary() {
        var container = []
        var columns = this.state[COLUMNS]
        for (var key in columns) {
            var id = 'summary-' + key
            if(true != columns[key].Attributes.unrender && false === isNaN(parseInt(columns[key].Attributes.summary))) {
                container.push(<td key={id}>{columns[key].Attributes.summary}</td>)
            } else if(true != columns[key].Attributes.unrender) {
                container.push(<td key={id}></td>)
            }
        }
        return <tr key='summary'>{container}</tr>
    }
    addText(key) {
        var th = 'grid-col-' + key
        var columns = this.state[COLUMNS]
        return <th className={th} key={key}>
            {this.addLabel(key)}
            <input id={columns[key].Attributes.id}
                className={columns[key].Attributes.class}
                data={columns[key].Attributes.data}
                onBlur={this.change.bind(this, key)}
                onChange={this.change.bind(this, key)}
                onKeyPress={this.submit.bind(this, key)}
                style={columns[key].Attributes.style}
                value={columns[key].Attributes.value}
                type='text' />
        </th>
    }
    change(key, event) {
        var state = []
        state[COLUMNS] = this.state[COLUMNS]
        if('select-multiple' == event.target.type) {
            var options = event.target.options
            var value = [];
            for (var i = 0, l = options.length; i < l; i++) {
                if (options[i].selected) {
                    value.push(options[i].value);
                }
            }
            state[COLUMNS][key].Attributes.value = value
        } else {
            state[COLUMNS][key].Attributes.value = event.target.value
        }
        this.setState(state)
    }
    done(payload, key) {
        var response = $.ajax({ type:'post',url:this.state[BUTTONS]['done'].link,data:payload,async:false}).responseJSON
        var buttons = this.state[BUTTONS]
        buttons['done'].href = response.link
        buttons['done'].style = {display:'block'}
        buttons['send'].class = 'btn btn-success disabled'
        buttons[key].width = 100
        var state = []
        state[BUTTONS] = buttons
        this.setState(buttons)
    }
    edit(data) {
        if("Object" != data.constructor.name && "object" == typeof(data)) {
            data = new Object()
        }
        var link = this.state[BUTTONS].dialogs['edit']
        $.ajax({
            type: 'post',
            data: data,
            url: link,
            async: false,
            success: function (payload) {
                $('#masala-edit-modal-body').html('');
                $(payload).appendTo('#masala-edit-modal-body');
                return payload
            }
        })
    }
    filter() {
        var data = this.getSpice()
        var link = this.state[BUTTONS]['filter']
        return $.ajax({
            type: 'post',
            data: data,
            url: link,
            async: false,
            success: function (payload) {
                return payload
            }
        }).responseJSON
    }
    getOptions(key) {
        var container = []
        var columns = this.state[COLUMNS]
        var options = columns[key].Attributes.data
        var option = key + '-'
        if(undefined != options['']) {
            var id = options['']
            container.push(<option key={option} value=''>{id}</option>)
        }
        for (var value in options) {
            if('' != value) {
                var id = options[value]
                var option = key + '-' + value
                container.push(<option key={option} value={value}>{id}</option>)
            }
        }
        return container
    }
    getPage(container, i) {
        if(this.state[BUTTONS]['page'] + i <= this.state[BUTTONS]['pages']) {
            var page = 'page' + (this.state[BUTTONS]['page'] + i)
            if(0 == i) {
                container.push(<li className='page-item active' key={page}><a onClick={this.setPage.bind(this, this.state[BUTTONS]['page'] + i)}>{this.state[BUTTONS]['page'] + i}</a></li>)
            } else {
                container.push(<li className='page-item' key={page}><a onClick={this.setPage.bind(this, this.state[BUTTONS]['page'] + i)}>{this.state[BUTTONS]['page'] + i}</a></li>)
            }

        }
        return container
    }
    getSpice() {
        var spice = new Object()
        var sort = new Object()
        var columns = this.state[COLUMNS]
        for (var key in columns) {
            if(undefined != columns[key].Attributes.value && '' != columns[key].Attributes.value && false == $.isEmptyObject(columns[key].Attributes.value)) {
                spice[key] = columns[key].Attributes.value;
            }
            if(undefined != columns[key].Attributes.order) {
                sort[key] = columns[key].Attributes.order
            }
        }
        var url = this.state[BUTTONS]['link'] + JSON.stringify(spice) +  '&masala-page=' + this.state[BUTTONS]['page']  + '&masala-sort=' + JSON.stringify(sort)
        window.history.replaceState('', 'title', url)
        var data = new Object()
        data.filters = spice
        data.sort = sort
        data.offset = this.state[BUTTONS]['page']
        return data
    }
    paginate() {
        var data = this.getSpice()
        var response = $.ajax({
            type: 'post',
            data: data,
            async: false,
            url: this.state[BUTTONS]['paginate'],
            success: function (payload) {
                return payload
            }
        }).responseText
        var state = []
        state[BUTTONS] = this.state[BUTTONS]
        state[BUTTONS]['pages'] = response
        this.setState(state)
        return data
    }
    prepare(event) {
        var data = this.getSpice()
        var element = this.state[BUTTONS][event.target.id]
        element.class = 'btn btn-success disabled'
        var self = this
        $.ajax({type: 'post', url: this.state[BUTTONS][event.target.id].link, data: data, async:false, success: function(payload) {
            var state = []
            state[event.target.id] = element
            self.setState(state)
            self.run(payload, 'export')
        }})
    }
    render() {
        var dialogs = []
        for(var dialog in this.state[DIALOGS]) {
            dialogs.push(<a
                className='btn btn-success'
                id={dialog}
                key={'dialog-' + dialog}
                type='button'
                data-target={'#masala-' + dialog}
                data-toggle='modal'
                onClick={this.bind(this.state[DIALOGS][dialog].onClick, this.state[DIALOGS][dialog].link)}
                style = {{marginRight: '10px'}}
                title='edit'>{this.state[DIALOGS][dialog].label}</a>)
        }
        return <div><ul key='paginator' id='paginator' className='pagination'>{this.addPaginator()}</ul>
            <table><tbody>
                <tr><td>{this.addProgressBar('export')}{this.addFilters()}</td></tr>
                <tr><td style={{paddingTop:'10px'}}>
                    {dialogs}{this.addAction('setting')}{this.addAction('excel')}{this.addAction('export')}{this.addAction('reset')}{this.addAction('send')}{this.addAction('done')}
                </td></tr>
            </tbody></table>
            {this.addSettings()}
            <table className="table table-striped table-hover">
            <thead>
            <tr className='grid-labels'>{this.addLabels()}</tr>
            <tr className='grid-columns'>{this.addColumns()}</tr>
            </thead>
            <tbody>{this.addBody()}</tbody>
        </table></div>
    }
    reset() {
        var state = []
        var columns = this.state[COLUMNS]
        for(var filter in columns) {
            if('object' == typeof(columns[filter].Attributes.value)) {
                columns[filter].Attributes.value = []
            } else if('string' == typeof(columns[filter].Attributes.value)) {
                columns[filter].Attributes.value = ''
            }
            columns[filter].Attributes.order = null
        }
        state[COLUMNS] = columns
        var buttons = this.state[BUTTONS]
        buttons['done'].style = {display:'none'}
        buttons['send'].class = 'btn btn-success'
        if(undefined != this.state[BUTTONS]['export'].class) {
            buttons['export'].width = 0
            buttons['export'].class = 'btn btn-success'
        }
        state[BUTTONS] = buttons
        this.setState(state)
        this.getSpice()
    }
    remove(key) {
        this.signal({target:{id:key,href:this.state[BUTTONS].remove},preventDefault(){}})
    }
    run(payload, key) {
        var self = this
        if(parseInt(payload.stop) > parseInt(payload.offset)) {
            $.ajax({ type:'post',url:this.state[BUTTONS]['run'], data:payload,success: function(payload) { self.run(payload, key) }})
            var buttons = this.state[BUTTONS]
            buttons[key].width = payload.offset / (payload.stop / 100)
            var state = []
            state[BUTTONS] = buttons
            this.setState(state)
        } else {
            this.done(payload, key)
        }
    }
    setPage(page) {
        if(page <= this.state[BUTTONS]['pages']) {
            var state = []
            state[BUTTONS] = this.state[BUTTONS]
            state[BUTTONS]['page'] = page
            state[ROWS] = this.filter()
            this.setState(state)
            this.summary(this.paginate())
        }
    }
    signal(event) {
        event.preventDefault()
        if(false === confirm(this.state[BUTTONS]['proceed'])) {
            return
        }
        var response = $.ajax({
            type: 'post',
            data: this.state[ROWS][event.target.id],
            url: event.target.href,
            async: false,
            success: function (payload) {
            }
        }).responseJSON
        if(true === response.remove) {
            var element = this.state[ROWS]
            delete element[event.target.id]
            var state = []
            state[ROWS] = element
            this.setState(state)
        } else if(true === response.submit) {
            this.submit()
        }
    }
    setting(event) {
        var state = []
        var buttons = this.state[BUTTONS]
        if('checkbox' === event.target.type) {
            var columns = this.state[COLUMNS]
            if(true == columns[event.target.id].Attributes.unrender) {
                columns[event.target.id].Attributes.unrender = false
            } else {
                columns[event.target.id].Attributes.unrender = true
            }
            var data = new Object()
            data[event.target.id] = columns[event.target.id].Attributes.unrender
            state[COLUMNS] = columns
            $.ajax({ type:'post',url:this.state[BUTTONS].setting.link,data:data,async:false})
        } else {
            if('block' == buttons.setting.display) {
                buttons.setting.display = 'none'
            } else {
                buttons.setting.display ='block'
            }
            state[BUTTONS] = buttons
        }
        this.setState(state)
    }
    submit(key, event) {
        var state = []
        if('object' == typeof(event) && 'Enter' == event.key) {
            var columns = this.state[COLUMNS]
            var element = columns[key]
            element.Attributes.value = event.target.value
            state[key] = element
        } else if('object' == typeof(event) && 'react-click' != event.type) {
            return
        }
        state[ROWS] = this.filter()
        state[BUTTONS] = this.state[BUTTONS]
        state[BUTTONS]['page'] = 1
        this.setState(state)
        this.summary(this.paginate())
    }
    summary(data) {
        var columns = this.state[COLUMNS]
        for (var key in columns) {
            if(true == columns[key].Attributes.unrender || false === columns[key].Attributes.summary) { } else {
                data['summary'] = key
                columns[key].Attributes.summary = $.ajax({ type:'post',url:this.state[BUTTONS]['summary'],data:data,async:false}).responseText
            }
        }
        var state = []
        state[COLUMNS] = columns
        this.setState(state)
    }
    sort(key) {
        var columns = this.state[COLUMNS]
        var element = columns[key]
        if(undefined == element.Attributes.order || 'asc' == element.Attributes.order) {
            element.Attributes['order'] = 'desc'
        } else {
            element.Attributes['order'] = 'asc'
        }
        var state = []
        state[key] = element
        state[BUTTONS] = this.state[BUTTONS]
        state[BUTTONS]['page'] = 1
        this.setState(state)
        this.submit()
    }
    update(key, column, event) {
        if('object' == typeof(event)) {
            var data = this.state[ROWS][key]
            data[column] = event.target.value
            var state = []
            state[ROWS] = this.state[ROWS]
            state[ROWS][key] = $.ajax({type:'post', url:this.state[BUTTONS]['update'],data:data,async:false}).responseJSON
            this.setState({state})
        }
    }

}
var dom = ReactDOM.render(<Grid />, document.getElementById('grid'));
dom.submit()