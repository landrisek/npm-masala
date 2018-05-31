import {AreaChart} from 'react-easy-chart';
import axios from 'axios'
import Datetime from 'react-datetime'
import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import request from 'sync-request'

var BUTTONS = 'buttons'
var COLUMNS = 'columns'
var CHARTS = 'charts'
var EDIT = 'edit'
var ID = 'grid'
var ITERATOR = 1
var LISTENERS = 'listeners'
var LISTS = 'lists'
var NEW = 'new'
var ROW = 'row'
var ROWS = 'rows'
var SIZE = 0
var VALIDATORS = 'validators'

export default class Grid extends Component {
    constructor(props) {
        super(props)
        this.state = JSON.parse(document.getElementById(ID).getAttribute('data'))
    }
    addAction(key) {
        return <div key={'trigger-' + key} style={this.state[BUTTONS][key].style}><a className={this.state[BUTTONS][key].className}
                    id={'trigger-' + key}
                    href={this.state[BUTTONS][key].href}
                    >{this.state[BUTTONS][key].Label}</a>
            <a className={this.state[BUTTONS][key].className}
                href={this.state[BUTTONS][key].href}
                id={key}
                key={key}
                style={{display:'none'}}
                onClick={this.bind(this.state[BUTTONS][key].onClick)}>{this.state[BUTTONS][key].Label}</a>
            </div>
    }
    addActions(action, key) {
        var container = []
        if(undefined == this.state[BUTTONS][action].length) {
            if('chart' == action) {
                var className = 'bar-chart'
            } else {
                var className = action
            }
            container.push(<td key={'grid-col-' + key + '-actions'}><a
                id={key}
                className={'fa-hover fa fa-' + className}
                data-target={'#masala-' + action}
                data-toggle='modal'
                onClick={this.bind(action)}
                title={key}></a></td>)
        }
        return container
    }
    addButton(key) {
        return <div key={'elements-' + key} class={'form-control col-xy-sm'}>
            <a className={this.state[COLUMNS][key].Attributes.className}
               href={this.state[COLUMNS][key].Attributes.href}
               onClick={this.bind(this.state[COLUMNS][key].Attributes.onClick)}
               id={key}
               key={key}
               title={this.state[COLUMNS][key].Attributes.title}>{this.state[COLUMNS][key].Attributes.Label}</a>
        </div>
    }
    addBody() {
        var body = []
        var i = 0
        var rows = []
        for(var key in this.state[NEW]) {
            rows[i++] = this.state[NEW][key]
        }
        for(var key in this.state[ROWS]) {
            rows[i++] = this.state[ROWS][key]
        }
        body.push(this.addSummary())
        for(var key in rows) {
            var id = 'row-' + key;
            body.push(<tr id={id} style={rows[key].style} key={id}>{this.addRow(rows[key], key)}</tr>)
            if('object' == typeof(this.state[CHARTS][key])) {
                var clone = document.getElementById('row-' + key)
                body.push(<AreaChart
                    xType='text'
                    axes
                    data={this.state[CHARTS][key]}
                    xTicks={5}
                    yTicks={3}
                    dataPoints
                    key={'chart-' + key}
                    grid
                    height={200}
                    noAreaGradient
                    tickTimeDisplayFormat={'%d %m'}
                    interpolate='cardinal'
                    style={{position:'absolute'}}
                    width={clone.offsetWidth}
                />)
                body.push(<tr height={200} width={clone.offsetWidth}></tr>)
            }
        }
        SIZE = key
        return body
    }
    addCheckbox(key) {
        var columns = this.state[COLUMNS]
        var checked = ''
        if('clicked' == columns[key].Attributes.value) {
            checked = 'checked'
        }
        return <div key={'elements-' + key}>
            {this.addLabel(key)}
            <input id={key}
                   className={columns[key].Attributes.className}
                   data={columns[key].Attributes.data}
                   onClick={this.push.bind(this)}
                   onChange={this.change.bind(this)}
                   style={columns[key].Attributes.style}
                   value={columns[key].Attributes.value}
                   checked={checked}
                   type='checkbox' />
        </div>
    }
    addColumns() {
        var body = []
        var columns = this.state[COLUMNS]
        ITERATOR = 1
        for (var key in columns) {
            var closure = this[columns[key].Method]
            if('function' == typeof(closure) && false == columns[key].Attributes.filter && false == columns[key].Attributes.unrender) {
                body.push(<th className={'grid-col-' + key} key={key} style={columns[key].Attributes.style}>{this[columns[key].Method](key)}</th>)
            } else if('function' == typeof(closure) && true == columns[key].Attributes.filter && false == columns[key].Attributes.unrender) {
                body.push(this.addEmpty(key))
            }
        }
        return body
    }
    addDate(data) {
        return <div><Datetime locale={data.Attributes.locale}
                         id={data.Attributes.id}
                         name={data.Attributes.name}
                         onChange={this.date.bind(this, {target:data.Attributes})}
                         value={data.Attributes.value} /></div>
    }
    addDateTime(key) {
        return <div key={'elements-' + key}>
            {this.addLabel(key)}
            <Datetime locale={this.state[COLUMNS][key].Attributes.locale}
                         onChange={this.datetime.bind(this, key)}
                         value={this.state[COLUMNS][key].Attributes.value}
        /></div>
    }
    datetime(key, event) {
        var state = []
        state[COLUMNS] = this.state[COLUMNS]
        if('string' == typeof(event)) {
            state[COLUMNS][key].Attributes.value = event
        } else {
            state[COLUMNS][key].Attributes.value = event.format(state[COLUMNS][key].Attributes.format.toUpperCase())
        }
        this.setState(state)
    }
    addDialog() {
        var rows = this.state[EDIT][this.state[ROW]]
        var container = []
        for(var row in rows) {
            var key = this.state[ROW] + '-' + row
            if('addHidden' == rows[row].Method) {
                container.push(<input key={row} value={rows[row].Label} type='hidden' />)
            } else if('_submit' == row) {
                container.push(<div key={'dialogs-' + key}><input className='form-control btn-success'
                                                                  id={rows[row].Attributes.id}
                                                                  name={rows[row].Attributes.name}
                                                                  onClick={this.save.bind(this)}
                                                                  value={rows[row].Label}
                                                                  type='submit' /></div>)
            } else if('_message' == row) {
                container.push(<div className={rows[row].Attributes.className} key={key} style={rows[row].Attributes.style}>{rows[row].Label}</div>)
            } else if('select' == rows[row].Tag) {
                var data = []
                var options = rows[row].Attributes.data
                for(var value in options) {
                    if(value == rows[row].Attributes.value) {
                        data.push(<option key={'dialogs-' + key + '-' + value} selected='selected' value={value}>{options[value]}</option>)
                    } else {
                        data.push(<option key={'dialogs-' + key + '-' + value} value={value}>{options[value]}</option>)
                    }
                }
                container.push(<div key={'labels-' + row}><label>{rows[row].Label}</label></div>)
                container.push(<select className='form-control'
                                       id={rows[row].Attributes.id}
                                       key={'dialogs-' + key}
                                       name={rows[row].Attributes.name}
                                       onChange={this.type.bind(this)}>{data}></select>)
            } else if ('addDateTime' == rows[row].Method) {
                container.push(<div key={'labels-' + row}><label>{rows[row].Label}</label></div>)
                container.push(<div key={'dialogs-' + key}>{this.addDate(rows[row])}</div>)
            } else {
                rows[row].Attributes.onChange = this.type.bind(this)
                container.push(<div key={'labels-' + row}><label>{rows[row].Label}</label></div>)
                container.push(<div key={'dialogs-' + key}>{React.createElement(rows[row].Tag, rows[row].Attributes)}</div>)
            }
            if(null == this.state[VALIDATORS][row]) { } else {
                container.push(<div key={'validator-' + key} className='bg-danger'>{this.state[VALIDATORS][row]}</div>)
            }
        }
        return <div><a id='trigger-message' style={{display:'hidden'}} data-target={'#masala-edit'} data-toggle='modal'></a>
            <div className='modal fade' id={'masala-edit'} tabIndex={-1} role='dialog' aria-labelledby='edit' aria-hidden='true' style={{zIndex:'1099'}}>
                <div className='modal-dialog'>
                    <div className='modal-content'>
                        <div className='modal-header'>
                            <button type='button' className='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                            <h4 className='modal-title' id={'masala-label-edit'}>{this.state[BUTTONS].edit.Label}</h4>
                        </div>
                        <div className='modal-body' id={'masala-edit-modal-body'}>{container}</div>
                    </div>
                </div>
            </div></div>
    }
    addEmpty(key) {
        return <th className={'grid-col-' + key} key={key}></th>
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
        return body
    }
    addHidden(key) {
        return <th class={'grid-col-' + key} key={key}></th>
    }
    addMultiSelect(key) {
        var values = new Object()
        var selected = false
        for(var value in this.state[COLUMNS][key].Attributes.value) {
            values[this.state[COLUMNS][key].Attributes.value[value]] = true
            selected = true
        }
        var container = []
        var options = []
        var select = ''
        var total = 0
        var alt = null
        for(var value in this.state[COLUMNS][key].Attributes.data) {
            if(undefined != values[value]) {
                container.push(<li className='list-group-item' 
                                   id={key}
                                   key={key + '-' + value}
                                   onClick={this.click.bind(this)}
                                   value={value}>
                        {this.state[COLUMNS][key].Attributes.data[value]}<span id={key} className='glyphicon glyphicon-remove' value={value} style={{float:'right'}}></span>
                    </li>)
            } else if(this.state[COLUMNS][key].Attributes.autocomplete.trim().length == 0 || null != this.state[COLUMNS][key].Attributes.data[value].toLowerCase().match(this.state[COLUMNS][key].Attributes.autocomplete.toLowerCase())) {
                var style = {}
                if(total == this.state[COLUMNS][key].Attributes.position) {
                    style = {backgroundColor:'rgb(51, 122, 183)',color:'white'}
                    alt = value
                }
                options.push(<li className='list-group-item'
                                 id={key}
                                 key={key + '-' + value}
                                 onClick={this.change.bind(this)}
                                 onKeyDown={this.key.bind(this)}
                                 style={style}
                                 value={value}>{this.state[COLUMNS][key].Attributes.data[value]}</li>)
                total++
            }
        }
        if(true == selected) {
            select = <ul className='list-group'>{container}</ul>
        }
        return <div key={'elements-' + key}>{this.addLabel(key)}
            {select}
            <div className='input-group'>
                <input alt={alt}
                       className='form-control'
                       name={total}
                       key={key + '-autocomplete'}
                       id={key}
                       onChange={this.autocomplete.bind(this)}
                       onKeyDown={this.key.bind(this)}
                       onClick={this.show.bind(this)}
                       placeholder={this.state[COLUMNS][key].Attributes.placeholder}
                       type='text'
                       value={this.state[COLUMNS][key].Attributes.autocomplete} />
                <div className='input-group-btn'>
                    <ul className='dropdown-menu dropdown-menu-right list-group'
                        id={key}
                        size={this.state[COLUMNS][key].Attributes.size}
                        style={{position:'absolute',zIndex:99,maxHeight:'300px',overflowY:'scroll',display:this.state[COLUMNS][key].Attributes.style.display}}>
                        {options}
                    </ul>
                </div>
            </div>
        </div>
    }
    addLabel(key) {
        if(true == this.state[COLUMNS][key].Attributes.filter) {
            return <label>{this.state[COLUMNS][key].Label}</label>
        }
    }
    addLabels() {
        var labels = []
        var columns = this.state[COLUMNS]
        for (var key in columns) {
            if(true != columns[key].Attributes.unrender && true == columns[key].Attributes.unsort) {
                labels.push(<th key={key + '-sort'} >{columns[key].Label}</th>)
            } else if (true != columns[key].Attributes.unrender) {
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
    addSettings() {
        if(false == this.state[BUTTONS].setting) {
            return
        }
        var settings = []
        var columns = this.state[COLUMNS]
        for (var key in columns) {
            if(null == /\s/.exec(key) && 'groups' != key) {
                var checked = 'checked'
                if(true == columns[key].Attributes.unrender) { checked = null }
                settings.push(<div key={key + '-setting'} style={{float:'left'}}>
                    <input defaultChecked={checked} id={key} onClick={this.setting.bind(this)} type='checkbox' />&nbsp;&nbsp;{columns[key].Label}&nbsp;&nbsp;
                </div>)
            }
        }
        return <div style={{display:this.state[BUTTONS].setting.display}}>{settings}</div>
    }
    addPaginator() {
        var container = []
        var extent = 9
        if(this.state[BUTTONS].page > 1) {
            container.push(<li key='first-page' className='page-item'><a onClick={this.setPage.bind(this, 1)}>1</a></li>)
            container.push(<li key='previous-page'><a aria-label='Previous' onClick={this.setPage.bind(this, this.state[BUTTONS].page - 1)}><span aria-hidden="true">&laquo;</span></a></li>)
        }
        var i = 0
        while(i < extent) {
            container = this.getPage(container, i++)
        }
        if(this.state[BUTTONS].pages > i) {
            container.push(<li key='next-page'><a aria-label='Next' onClick={this.setPage.bind(this, this.state[BUTTONS].page + i)}><span aria-hidden="true">&raquo;</span></a></li>)
        }
        if(this.state[BUTTONS].pages > this.state[BUTTONS].page && this.state[BUTTONS].page > extent) {
            container.push(<li key='last-page' className='page-item'><a onClick={this.setPage.bind(this, this.state[BUTTONS].pages)}>{this.state[BUTTONS].pages}</a></li>)
            container.push(<li key='last-page'><a aria-label='Previous' onClick={this.setPage.bind(this, this.state[BUTTONS].pages)}><span aria-hidden="true">{this.state[BUTTONS].pages}</span></a></li>)
        }
        return container
    }
    addProgressBar(key){
        var id = key + '-progress'
        if(this.state[BUTTONS][key].length > 0 || undefined === this.state[BUTTONS][key].length || undefined === this.state[BUTTONS].process.length) {
            return <div key={id} className='progress'><div className='progress-bar' style={{width:this.state[BUTTONS][key].width+'%'}}></div></div>
        }
    }
    addRow(rows, key) {
        var container = []
        var actions = false
        for(var row in rows) {
            actions = true
            if(undefined != this.state[COLUMNS][row] && true != this.state[COLUMNS][row].Attributes.unrender) {
                if('object' == typeof(rows[row]) && null !== rows[row]) {
                    rows[row].Attributes.id = row
                    rows[row].Attributes.name = key
                    rows[row].Attributes.onChange = this.update.bind(this)
                    if(null == rows[row].Attributes.value) {
                        rows[row].Attributes.value = ''
                    }
                    if('function' == typeof(this[rows[row].Method])) {
                        var element = this[rows[row].Method](rows[row])
                    } else {
                        var element = React.createElement(rows[row].Tag, rows[row].Attributes, rows[row].Label)
                    }
                    container.push(<td id={'grid-col-' + row} key={'grid-col-' + key + row} style={rows[row].Attributes.style}>{element}</td>)
                } else {
                    container.push(<td id={'grid-col-' + row} key={'grid-col-' + key + row}>{rows[row]}</td>)
                }
            } else if('_actions' == row) {
                var actions = []
                for(var action in rows[row]) {
                    var element = React.createElement(rows[row][action].Tag, rows[row][action].Attributes, rows[row][action].Label)
                    actions.push(<span key={'_actions' + action} style={{marginRight:'10px'}}>{element}</span>)
                }
                container.push(<td id={'grid-col-' + row} key={'grid-col-' + key + row}>{actions}</td>)
            }
        }
        if(true == actions) {
            container.push(this.addActions('edit', key))
            container.push(this.addActions('chart', key))
            container.push(this.addActions('remove', key))
        }
        return container
    }
    addSelect(key) {
        return <div key={'elements-' + key}>
                {this.addLabel(key)}
                <select className={this.state[COLUMNS][key].Attributes.className}
                        id={key}
                        onChange={this.change.bind(this)}
                        >{this.getOptions(key)}>
                </select></div>
    }
    addSummary() {
        var container = []
        for (var key in this.state[COLUMNS]) {
            var id = 'summary-' + key
            if(true != this.state[COLUMNS][key].Attributes.unrender && false === isNaN(parseInt(this.state[COLUMNS][key].Attributes.summary))) {
                container.push(<td key={id}>{this.state[COLUMNS][key].Attributes.summary}</td>)
            } else if(true != this.state[COLUMNS][key].Attributes.unrender) {
                container.push(<td key={id}></td>)
            }
        }
        return <tr id='masala-summary' key='masala-summary'>{container}</tr>
    }
    addText(key) {
        return <div key={'element-' + key}>{this.addLabel(key)}
                <input id={key}
                className={this.state[COLUMNS][key].Attributes.className}
                data={this.state[COLUMNS][key].Attributes.data}
                onBlur={this.change.bind(this)}
                onChange={this.change.bind(this)}
                onKeyPress={function(event) { if('Enter' == event.key) { $('#trigger-send').trigger('click') } }}
                style={this.state[COLUMNS][key].Attributes.style}
                value={this.state[COLUMNS][key].Attributes.value}
                type='text' />
                </div>
   }
    autocomplete(event) {
        var state = []
        state[COLUMNS] = this.state[COLUMNS]
        if('none' == state[COLUMNS][event.target.id].Attributes.style.display) {
            state[COLUMNS][event.target.id].Attributes.style.display = 'block'
        }
        state[COLUMNS][event.target.id].Attributes.autocomplete = event.target.value
        this.setState(state)
    }
    bind(method) {
        if(undefined === method) {
            return
        }
        var closure = method.replace(/\(/, '').replace(/\)/, '')
        if('function' == typeof(this[closure])) {
            return this[closure].bind(this)
        }
    }
    change(event) {
        var state = []
        state[COLUMNS] = this.state[COLUMNS]
        if('click' == event.type) {
            state[COLUMNS][event.target.id].Attributes.autocomplete = ''
            state[COLUMNS][event.target.id].Attributes.position = 0
            state[COLUMNS][event.target.id].Attributes.value.push(event.target.getAttribute('value'))
            if('_' == event.target.getAttribute('value')) {
                state[COLUMNS][event.target.id].Attributes.value = []
            }
        } else if('checkbox' == event.target.type && 'on' == event.target.value) {
            state[COLUMNS][event.target.id].Attributes.value = 'clicked'
        } else if('checkbox' == event.target.type && 'clicked' == event.target.value) {
            state[COLUMNS][event.target.id].Attributes.value = 'on'
        } else {
            state[COLUMNS][event.target.id].Attributes.value = event.target.value
        }
        this.setState(state)
    }
    chart(event) {
        var response = JSON.parse(request('POST', this.state[BUTTONS].chart.link, { json: {spice:this.getSpice(),Row:this.state[ROWS][event.target.id] }}).getBody('utf8'))
        var state = []
        if(undefined == this.state[CHARTS][event.target.id]) {
            state[CHARTS] = this.state[CHARTS]
            state[CHARTS][event.target.id] = response
            this.setState(state)
        } else {
            state[CHARTS] = this.state[CHARTS]
            delete state[CHARTS][event.target.id]
            this.setState(state)
        }
    }
    charts(event) {
        for(var row in this.state[ROWS]) {
            this.chart({target:{id:row}})
        }
    }
    click(event) {
        var state = []
        state[COLUMNS] = this.state[COLUMNS]
        for(var key in this.state[COLUMNS][event.target.id].Attributes.value) {
            if(event.target.getAttribute('value') == state[COLUMNS][event.target.id].Attributes.value[key]) {
                delete state[COLUMNS][event.target.id].Attributes.value[key]
            }
        }
        this.setState(state)
    }
    componentWillMount() {
        document.addEventListener('click', this.hide.bind(this));
        if(this.state[LISTENERS].length > 0) {
            document.addEventListener('keydown', this.keyDown.bind(this));
        }
    }
    componentWillUnmount() {
        this.forceUpdate()
        document.removeEventListener('click', this.hide.bind(this));
        if(this.state[LISTENERS].length > 0) {
            document.removeEventListener('keydown', this.keyDown.bind(this));
        }
    }
    date(event, time) {
        var date = time.format(event.target.format.toUpperCase())
        var state = []
        state[ROWS] = this.state[ROWS]
        state[EDIT] = this.state[EDIT]
        if(undefined != state[EDIT][event.target.name]) {
            state[EDIT][event.target.name][event.target.id].Attributes.value = date
        }
        if('object' == typeof(state[ROWS][event.target.name][event.target.id])) {
            state[ROWS][event.target.name][event.target.id].Attributes.value = date
        } else {
            state[ROWS][event.target.name][event.target.id] = date
        }
        state[ROWS][event.target.name] = JSON.parse(request('POST', this.state[BUTTONS].update, { json: {Key:event.target.id,Row:state[ROWS][event.target.name],Submit:false} }).getBody('utf8'))
        this.setState(state)
    }
    done(payload, key) {
        var response = JSON.parse(request('POST', this.state[BUTTONS].done.link, { json: payload }).getBody('utf8'))
        if(undefined != response.redirect) {
            window.location.href = response.redirect
        }
        var buttons = this.state[BUTTONS]
        for(var attribute in response) {
            buttons.done[attribute] = response[attribute]
        }
        buttons.done.style.display = 'block'
        if(undefined != buttons.process.link) {
            buttons.process.className = 'btn btn-success'
        }
        buttons.send.className = 'btn btn-success'
        buttons[key].width = 100
        var state = []
        state[BUTTONS] = buttons
        this.setState(state)
    }
    edit(event) {
        var data = {id:event.target.id,Row:{}}
        var rows = this.state[ROWS][event.target.id]
        for(var row in rows) {
            if(null != rows[row] && null != rows[row].Label) {
                data.Row[row] = rows[row].Label
            } else if(null != rows[row] && null != rows[row].Attributes) {
                data.Row[row] = rows[row].Attributes.value
            } else {
                data.Row[row] = rows[row]
            }
        }
        var state = []
        state[ROW] = event.target.id
        state[EDIT] = this.state[EDIT]
        state[EDIT][event.target.id] = JSON.parse(request('POST', this.state[BUTTONS].edit.link, { json: data }).getBody('utf8'))
        this.setState(state)
    }
    filter() {
        return JSON.parse(request('POST', this.state[BUTTONS].filter, { json: this.getSpice() }).getBody('utf8'))
    }
    getOptions(key) {
        var container = []
        var options = this.state[COLUMNS][key].Attributes.data
        for(var value in options) {
            var id = options[value]
            var option = key + '-' + value
            if(value == this.state[COLUMNS][key].Attributes.value) {
                container.push(<option key={option} selected='selected' value={value}>{id}</option>)
            } else {
                container.push(<option key={option} value={value}>{id}</option>)
            }
        }
        return container
    }
    getPage(container, i) {
        if(this.state[BUTTONS].page + i <= this.state[BUTTONS].pages) {
            var page = 'page' + (this.state[BUTTONS].page + i)
            if(0 == i) {
                container.push(<li className='page-item active' key={page}><a onClick={this.setPage.bind(this, this.state[BUTTONS].page + i)}>{this.state[BUTTONS].page + i}</a></li>)
            } else {
                container.push(<li className='page-item' key={page}><a onClick={this.setPage.bind(this, this.state[BUTTONS].page + i)}>{this.state[BUTTONS].page + i}</a></li>)
            }

        }
        return container
    }
    getSpice() {
        var spice = new Object()
        var sort = new Object()
        var columns = this.state[COLUMNS]
        for (var key in columns) {
            if('string' == typeof(columns[key].Attributes.value) && columns[key].Attributes.value.length > 0) {
                spice[key] = columns[key].Attributes.value
            } else if(undefined != columns[key].Attributes.value) {
                for (var value in columns[key].Attributes.value) {
                    if(undefined == spice[key]) {
                        spice[key] = []
                    }
                    spice[key].push(columns[key].Attributes.value[value])
                }
            }
            if(undefined != columns[key].Attributes.order) {
                sort[key] = columns[key].Attributes.order
            }
        }
        var url = this.state[BUTTONS].link + JSON.stringify(spice) +  '&masala-page=' + this.state[BUTTONS].page  + '&masala-sort=' + JSON.stringify(sort)
        window.history.replaceState('', 'title', url)
        var data = new Object()
        data.Filters = spice
        data.Sort = sort
        data.Offset = this.state[BUTTONS].page
        return data
    }
    hide() {
        var update = false
        for(var list in this.state[LISTS]) {
            if('block' == this.state[COLUMNS][list].Attributes.style.display && 'undefined' == typeof(state)) {
                var state = []
                state[COLUMNS] = this.state[COLUMNS]
            }
            if('block' == this.state[COLUMNS][list].Attributes.style.display || this.state[COLUMNS][list].Attributes.autocomplete.length > 0) {
                state[COLUMNS][list].Attributes.autocomplete = ''
                state[COLUMNS][list].Attributes.style.display = 'none'
                update = true
            }
        }
        if(true == update) {
            this.setState(state)
        }
    }
    key(event) {
        if(38 == event.keyCode || 40 == event.keyCode || 13 == event.keyCode) {
            var state = []
            state[COLUMNS] = this.state[COLUMNS]
            if(13 == event.keyCode && '_' != event.target.alt) {
                state[COLUMNS][event.target.id].Attributes.autocomplete = ''
                state[COLUMNS][event.target.id].Attributes.value.push(event.target.alt)
            } else if(event.target.name > this.state[COLUMNS][event.target.id].Attributes.position && 40 == event.keyCode) {
                state[COLUMNS][event.target.id].Attributes.position++
                this.setState(state)
            } else if(this.state[COLUMNS][event.target.id].Attributes.position >= event.target.name && 40 == event.keyCode) {
                state[COLUMNS][event.target.id].Attributes.position = 0
            } else if(this.state[COLUMNS][event.target.id].Attributes.position == 0 && 38 == event.keyCode) {
                state[COLUMNS][event.target.id].Attributes.position = event.target.name
            } else if (this.state[COLUMNS][event.target.id].Attributes.position > 0 && 38 == event.keyCode) {
                state[COLUMNS][event.target.id].Attributes.position--
            }
            this.setState(state)
        }
    }
    keyDown(event) {
        for(var listener in this.state[LISTENERS]) {
            if(this.state[LISTENERS][listener] == event.keyCode) {
                var state = new Object()
                var data = {columns:this.state[COLUMNS],rows:this.state[ROWS]}
                state[ROWS] = JSON.parse(request('POST', this.state[BUTTONS].listen, { json: data }).getBody('utf8'))
                if('string' == typeof(state[ROWS])) {
                    this.message(state[ROWS])
                } else {
                    this.setState(state)
                }
            }
        }
    }
    message(message) {
        document.getElementById('masala-message-modal-body').insertAdjacentHTML('afterbegin', '<p>' + message + '</p>')
        $('#trigger-message').trigger('click')
    }
    paginate() {
        var data = this.getSpice()
        var response = request('POST', this.state[BUTTONS].paginate, { json: data }).getBody('utf8')
        var state = []
        state[BUTTONS] = this.state[BUTTONS]
        state[BUTTONS].pages = response
        state[CHARTS] = []
        this.setState(state)
        return data
    }
    prepare(event) {
        var data = this.getSpice()
        data.Offset = 0
        var response = JSON.parse(request('POST', this.state[BUTTONS][event.target.id].link, { json: data }).getBody('utf8'))
        if(undefined == response.Message) {
            var state = []
            var element = this.state[BUTTONS][event.target.id]
            element.className = 'btn btn-success disabled'
            state[event.target.id] = element
            this.setState(state)
            this.run(response, 'export')
        } else {
            this.message(response.Message)
            this.forceUpdate()
        }
    }
    push(event) {
        var data = this.getSpice()
        data.key = event.target.id
        data.columns = this.state[COLUMNS]
        data.rows = this.state[ROWS]
        delete data.rows[-1]
        var response = JSON.parse(request('POST', this.state[BUTTONS].push, { json: data }).getBody('utf8'))
        if(undefined == response.Message) {
            var state = new Object()
            state[ROWS] = response.rows
            if(this.state[BUTTONS].pages > 2) {
                state[BUTTONS] = this.state[BUTTONS]
                state[BUTTONS].pages = Math.round(parseFloat(state[BUTTONS].pages) + parseFloat(response.pages))
            }
            this.setState(state)
        } else {
            this.message(response.Message)
        }
        this.forceUpdate()
    }
    render() {
        var dialogs = []
        if(undefined == this.state[BUTTONS].edit.length) {
            dialogs.push(<a className='btn btn-success'
                             data-target={'#masala-edit'}
                             data-toggle='modal'
                             id='-1'
                             key='dialog-add'
                             onClick={this.edit.bind(this)}
                             style={{marginRight: '10px'}}
                             title='add'
                             type='button'
            >{this.state[BUTTONS].add.Label}</a>)
        }
        for(var dialog in this.state[BUTTONS].dialogs) {
            dialogs.push(this.addAction(this.state[BUTTONS].dialogs[dialog]))
        }
        document.getElementById('grid').style.display = 'block'
        var loader = document.getElementById('loader')
        if(null != loader) {
            loader.style.display = 'none'
        }
        return <div>
            <ul key='paginator' id='paginator' className='pagination'>{this.addPaginator()}</ul>
            <table style={{width:'100%'}}><tbody>
                <tr><td>{this.addProgressBar('export')}{this.addFilters()}</td></tr>
                <tr><td style={{paddingTop:'10px'}}>{dialogs}</td></tr>
            </tbody></table>
            {this.addSettings()}
            <table className='table table-striped table-hover' style={{position:'relative'}}>
                <thead id='masala-header'>
                    <tr className='grid-labels'>{this.addLabels()}</tr>
                    <tr className='grid-columns'>{this.addColumns()}</tr>
                </thead>
                <tbody>{this.addBody()}</tbody>
            </table>
            <ul key='down-paginator' id='down-paginator' className='pagination'>{this.addPaginator()}</ul>
            {this.addDialog()}
        </div>
    }
    reset() {
        var state = []
        var columns = this.state[COLUMNS]
        for(var filter in columns) {
            if('object' == typeof(columns[filter].Attributes.value)) {
                columns[filter].Attributes.value = []
            } else if('string' == typeof(columns[filter].Attributes.value) && 'addCheckbox' == columns[filter].Method) {
                columns[filter].Attributes.value = 'on'
            } else if('string' == typeof(columns[filter].Attributes.value) && 'addSelect' == columns[filter].Method) {
                columns[filter].Attributes.value = '_'
            } else if('string' == typeof(columns[filter].Attributes.value)) {
                columns[filter].Attributes.value = ''
            }
            columns[filter].Attributes.order = null
        }
        state[COLUMNS] = columns
        var buttons = this.state[BUTTONS]
        buttons.done.style = {display:'none'}
        buttons.send.className = 'btn btn-success'
        if(undefined != this.state[BUTTONS].export.className) {
            buttons.export.width = 0
            buttons.export.className = 'btn btn-success'
        }
        if(undefined != this.state[BUTTONS].excel.className) {
            buttons.excel.width = 0
            buttons.excel.className = 'btn btn-success'
        }
        state[BUTTONS] = buttons
        this.setState(state)
        this.getSpice()
        this.forceUpdate()
    }
    remove(event) {
        if(false === confirm(this.state[BUTTONS].proceed)) {
            return
        }
        JSON.parse(request('POST', this.state[BUTTONS].remove.link, { json: {spice:this.getSpice(),Row:this.state[ROWS][event.target.id] }}).getBody('utf8'))
        var state = []
        state[ROWS] = this.state[ROWS]
        delete state[ROWS][event.target.id]
        this.setState(state)
    }
    run(payload, key) {
        if(parseInt(payload.Stop) > parseInt(payload.Offset)) {
            axios.post(this.state[BUTTONS].run, payload).then(response => {
                var buttons = this.state[BUTTONS]
                buttons[key].width = payload.Offset / (payload.Stop / 100)
                var state = []
                state[BUTTONS] = buttons
                if('service' == response.data.Status && 'object' == typeof(response.data.Row) && SIZE > payload.Offset) {
                    state[ROWS] = this.state[ROWS]
                    for(var row in response.data.Row) {
                        state[ROWS][parseInt(payload.Offset)][row] = response.data.Row[row]
                    }
                }
                this.setState(state)
                this.run(response.data, key)
            })
        } else {
            this.done(payload, key)
        }
    }
    setPage(page) {
        if(page <= this.state[BUTTONS].pages) {
            var state = []
            state[BUTTONS] = this.state[BUTTONS]
            state[BUTTONS].page = page
            state[ROWS] = this.filter()
            this.setState(state)
            this.summary(this.paginate())
        }
    }
    setting(event) {
        var state = []
        var buttons = this.state[BUTTONS]
        if('checkbox' === event.target.type) {
            var columns = this.state[COLUMNS]
            if(true == columns[event.target.id].Attributes.unrender) {
                columns[event.target.id].Attributes.filter = false
                columns[event.target.id].Attributes.unrender = false
            } else {
                columns[event.target.id].Attributes.filter = true
                columns[event.target.id].Attributes.unrender = true
            }
            var data = new Object()
            data[event.target.id] = columns[event.target.id].Attributes.unrender
            state[COLUMNS] = columns
            request('POST', this.state[BUTTONS].setting.link, { json: data })
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
    show(event) {
        var state = []
        state[COLUMNS] = this.state[COLUMNS]
        state[COLUMNS][event.target.id].Attributes.style.display = 'block'
        this.setState(state)
    }
    start() {
        var state = this.state
        state[ROWS] = this.filter()
        state[BUTTONS] = this.state[BUTTONS]
        this.setState(state)
        this.summary(this.paginate())
    }
    submit(event) {
        var state = []
        if('object' == typeof(event) && 'Enter' == event.key) {
            var columns = this.state[COLUMNS]
            var element = columns[event.target.id]
            element.Attributes.value = event.target.value
            columns[event.target.id] = element
            state[COLUMNS] = columns
        } else if('object' == typeof(event) && 'click' != event.type) {
            return
        }
        state[BUTTONS] = this.state[BUTTONS]
        state[BUTTONS].page = 1
        this.setState(state)
        state[ROWS] = this.filter()
        this.setState(state)
        this.summary(this.paginate())
    }
    summary(data) {
        var columns = this.state[COLUMNS]
        for (var key in columns) {
            if(true == columns[key].Attributes.unrender || false === columns[key].Attributes.summary) { } else {
                data.summary = key
                columns[key].Attributes.summary = JSON.parse(request('POST', this.state[BUTTONS].summary, { json: data }).getBody('utf8'))
            }
        }
        var state = []
        state[COLUMNS] = columns
        this.setState(state)
    }
    sort(key) {
        var columns = this.state[COLUMNS]
        var element = columns[key]
        if(undefined == element.Attributes.order) {
            element.Attributes.order = 'asc'
        } else if('asc' == element.Attributes.order) {
            element.Attributes.order = 'desc'
        } else if ('desc' == element.Attributes.order) {
            element.Attributes.order = undefined
        }
        var state = []
        state[key] = element
        state[BUTTONS] = this.state[BUTTONS]
        state[BUTTONS].page = 1
        this.setState(state)
        this.submit()
    }
    toggle(event) {
        var state = []
        state[COLUMNS] = this.state[COLUMNS]
        if('block' == state[COLUMNS][event.target.id].Attributes.style.display) {
            state[COLUMNS][event.target.id].Attributes.style.display = 'none'
        } else {
            state[COLUMNS][event.target.id].Attributes.style.display = 'block'
        }
        this.setState(state)
    }
    save(event) {
        var state = []
        state[VALIDATORS] = JSON.parse(request('POST', this.state[BUTTONS].validate, {json: {Row:this.state[EDIT][event.target.name]}}).getBody('utf8'))
        for (var validator in state[VALIDATORS]) {
            this.setState(state)
            return
        }
        state[EDIT] = this.state[EDIT]
        state[ROWS] = this.state[ROWS]
        var edit = state[EDIT][event.target.name]
        for(var row in edit) {
            if(undefined != edit[row].Attributes.data && undefined != edit[row].Attributes.value && 'object' == typeof(state[ROWS][event.target.name][row])) {
                state[ROWS][event.target.name].value = edit[row].Attributes.data[edit[row].Attributes.value]
            } else {
                state[ROWS][event.target.name][row] = edit[row].Attributes.value
            }
        }
        var rows = JSON.parse(request('POST', this.state[BUTTONS].update, { json: {Row:state[ROWS][event.target.name],Submit:true}}).getBody('utf8'))
        if(event.target.name < 0) {
            state[NEW] = this.state[NEW]
            state[NEW][event.target.name] = rows
        } else {
            state[ROWS][event.target.name] = rows
        }
        state[EDIT][event.target.name]._message.Attributes.style = {display:'block'}
        this.setState(state)
        this.edit({target:{id:event.target.name}})
    }
    type(event) {
        var state = []
        state[ROWS] = this.state[ROWS]
        state[EDIT] = this.state[EDIT]
        state[EDIT][event.target.name][event.target.id].Attributes.value = event.target.value
        if('checkbox' == event.target.type && 1 == event.target.value) {
            state[EDIT][event.target.name][event.target.id].Attributes.value = 0
            delete state[EDIT][event.target.name][event.target.id].Attributes.checked
            if(undefined != state[ROWS][event.target.name]) {
                state[ROWS][event.target.name][event.target.id] = 0
            }
        } else if('checkbox' == event.target.type && 0 == event.target.value) {
            state[EDIT][event.target.name][event.target.id].Attributes.value = 1
            state[EDIT][event.target.name][event.target.id].Attributes.checked = 'checked'
            if(undefined != state[ROWS][event.target.name]) {
                state[ROWS][event.target.name][event.target.id] = 1
            }
        } else if(undefined == state[ROWS][event.target.name]) {
        } else if(null != state[ROWS][event.target.name][event.target.id] && null != state[ROWS][event.target.name][event.target.id].Label) {
            state[ROWS][event.target.name][event.target.id].Label = event.target.value
        } else {
            state[ROWS][event.target.name][event.target.id] = event.target.value
        }
        this.setState(state)
    }
    update(event) {
        var state = []
        state[ROWS] = this.state[ROWS]
        if('checkbox' == event.target.type && 1 == event.target.value) {
            delete state[ROWS][event.target.name][event.target.id].Attributes.checked
            state[ROWS][event.target.name][event.target.id].Attributes.value = 0
        } else if('checkbox' == event.target.type && 0 == event.target.value) {
            state[ROWS][event.target.name][event.target.id].Attributes.checked = 'checked'
            state[ROWS][event.target.name][event.target.id].Attributes.value = 1
        } else {
            state[ROWS][event.target.name][event.target.id].Attributes.value = event.target.value
        }
        console.log(event.target.id)
        state[ROWS][event.target.name] = JSON.parse(request('POST', this.state[BUTTONS].update, { json: {Key:event.target.id,Row:state[ROWS][event.target.name],Submit:false}}).getBody('utf8'))
        this.setState(state)
    }

}
var element = document.getElementById(ID)
if(null != element) {
    ReactDOM.render(<Grid />, document.getElementById(ID)).start()
}