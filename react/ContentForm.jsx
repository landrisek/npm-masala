import React, {Component} from 'react'
import ReactDOM from 'react-dom'

var CURRENT = 0
var CONTENT = 'content'
var EDIT = 'edit'
var LABELS = 'labels'
var LINKS = {}
var STATISTICS = 'statistics'
var SOURCE = 'source'
var WRITE = 'write'

/** @todo https://www.npmjs.com/package/react-tag-input */
export default class ContentForm extends Component {
    constructor(props){
        super(props);
        var name = this.constructor.name;
        this.state = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data'))
        LINKS = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data-links'))
    }
    addInsert(key) {
        return <div key={key}><label>{this.state[LABELS][key]}</label><input className='form-control'
                      id={key}
                      onChange={this.update.bind(this)}
                      onKeyPress={this.insert.bind(this)}
                      style={{width: 'auto'}}
                      value={this.state[CONTENT][key]}
        /></div>
    }
    addSelect(key) {
        var options = this.state[CONTENT][key]
        var container = []
        for (var value in options) {
            var id = key + '-' + value
            container.push(<option key={id} value={value}>{options[value]}</option>)
        }
        var id = 'edit-' + key
        container.push(<option key={id} value='edit'>edit...</option>)
        return <select className='form-control'
                       id={key}
                       key={key}
                       onClick={this.click.bind(this)}
                       onChange={this.edit.bind(this)}
                       style={{float:'left',width: 'auto'}} >
                    {container}
                </select>
    }
    addSource() {
        var options = this.state[SOURCE]
        var container = []
        for (var value in options) {
            container.push(<option key={'source-' + value} value={value}>{options[value]}</option>)
        }
        return <select className='form-control'
                       id={SOURCE}
                       key={SOURCE}
                       onChange={this.write.bind(this)}
                       style={{float:'left',width: 'auto'}} >
            {container}
        </select>
    }
    addStatistics() {
        var container = []
        for(var statistic in this.state[STATISTICS]) {
            container.push(<div key={statistic}>{statistic}:{this.state[STATISTICS][statistic]}</div>)
        }
        return <div key={STATISTICS}><label>{this.state[LABELS][STATISTICS]}</label><br/>{container}</div>
    }
    addSubmit(key) {
        return <input
            className='btn btn-success'
            key={key}
            onClick={this.submit.bind(this)}
            type='submit'
            value={this.state[LABELS].submit} />
    }
    addText(key) {
        var width = (this.state[CONTENT][key].length + 5) * 8
        width.toString()
        return <input className='form-control'
                      id={key}
                      key={key}
                      onChange={this.update.bind(this)}
                      onClick={this.click.bind(this)}
                      onDragOver={this.drop.bind(this)}
                      onDrop={this.drop.bind(this)}
                      onKeyDown={this.edit.bind(this)}
                      style={{fontWeight:'bold',float:'left',width:width}}
                      value={this.state[CONTENT][key]}/>
    }
    addWrite() {
        return <div key={WRITE}><label>{this.state[LABELS][WRITE]}</label><br/>{this.state[WRITE]}</div>

    }
    attached() {
        var body = [];
        for (var key in this.state[CONTENT]) {
            if('select' == key || 'plain' == key) {
            } else if('object' == typeof(this.state[CONTENT][key])) {
                body.push(this.addSelect(key))
            } else if('string' == typeof(this.state[CONTENT][key])) {
                body.push(this.addText(key))
            }
        }
        return body;
    }
    click(event) {
        CURRENT = event.target.id
    }
    change(event) {
        var element = this.state[CONTENT][event.target.id]
        element[0] = event.target.value
        var state = []
        state[event.target.id] = element
        this.setState(state)
    }
    drop(event) {
        console.log('drag')
    }
    edit(event) {
        if('Enter' != event.key && 'edit' != event.target.value) {
            return
        }
        var state = []
        state[CONTENT] = this.state[CONTENT]
        if(0 === event.target.value.length) {
            delete state[CONTENT][event.target.id]
        } else if(event.target.id == this.state[EDIT]) {
            state[CONTENT][event.target.id] = this.explode(state[CONTENT][event.target.id])
            state[EDIT] = -1
            this.setState(state)
        } else if(this.state[EDIT] > 0) {
            state[CONTENT][event.target.id] = this.implode(state[CONTENT][event.target.id])
            state[CONTENT][this.state[EDIT]] = this.explode(state[CONTENT][this.state[EDIT]])
            state[EDIT] = event.target.id
            this.setState(state)
        } else if('edit' === event.target.value) {
            state[EDIT] = event.target.id
            state[CONTENT][event.target.id] = this.implode(state[CONTENT][event.target.id])
            this.setState(state)
        }
        this.setState(state)
    }
    explode(input) {
        var output = new Object()
        var options = input.split(';')
        for(var option in options) {
            if(options[option].length > 0) {
                output[option] = options[option]
            }
        }
        return output
    }
    implode(options) {
        var output = ''
        for(var option in options) {
            if(options[option].length > 0) {
                output += options[option] + ';'
            }
        }
        return output
    }
    insert(event) {
        if('Enter' != event.key || '' === event.target.value) {
            return
        }
        var state = []
        state[CONTENT] = new Object()
        for(var key in this.state[CONTENT]) {
            if(false == isNaN(key) && key >= CURRENT) {
                state[CONTENT][parseInt(key) + 1] = this.state[CONTENT][key]
            } else if(false == isNaN(key)) {
                state[CONTENT][key] = this.state[CONTENT][key]
            }
        }
        state[CONTENT][CURRENT] = new Object()
        state[CONTENT][event.target.id] = ''
        if('select' == event.target.id) {
            state[CONTENT][CURRENT][0] = event.target.value
        } else if('plain' == event.target.id) {
            state[CONTENT][CURRENT] = event.target.value
        }
        this.setState(state)
    }
    render() {
        return <div key='content'>
                    <div>{this.addSource()}</div>
                    <div style={{clear:'both'}}></div>
                    <strong>Text</strong>
                    <div>{this.attached()}</div>
                    <div style={{clear:'both'}}></div>
                    <form>{this.addInsert('select')}{this.addInsert('plain')}{this.addSubmit('submit')}</form>
                    {this.addWrite()}
                    {this.addStatistics()}
                </div>
    }
    submit(event) {
        event.preventDefault()
        var i=0;
        var data = new Object()
        data[CONTENT] = new Object()
        for(var key in this.state[CONTENT]) {
            if(false == isNaN(key)) {
                data[CONTENT][i++] = this.state[CONTENT][key]
            } else {
                data[CONTENT][key] = this.state[CONTENT][key]
            }
        }
        $.ajax({type: 'post', data: data, url: LINKS['submit'], async: false})
    }
    update(event) {
        var state = []
        state[CONTENT] = this.state[CONTENT]
        if(0 === event.target.value.length) {
            delete state[CONTENT][event.target.id]
        } else {
            state[CONTENT][event.target.id] = event.target.value
        }
        this.setState(state)
    }
    write(event) {
        var contents = this.state[CONTENT]
        var data = new Object()
        data.keywords = this.state[SOURCE][event.target.value]
        data.wildcards = $.ajax({type: 'post', data: data, url: LINKS['keyword'], async: false}).responseJSON
        var state = []
        state[WRITE] = ''
        state[STATISTICS] = new Object()
        for(var content in contents) {
            if('object' == typeof(contents[content])) {
                data.options = contents[content]
                data.write = state[WRITE]
                var response = $.ajax({type: 'post', data: data, url: LINKS['write'], async: false}).responseJSON
                state[WRITE] += contents[content][response.option] + ' '
                state[STATISTICS][response.options[response.option]] = response.max
            } else {
                state[WRITE] += contents[content] + ' '
            }
        }
        this.setState(state)
    }
}
ReactDOM.render(<ContentForm />, document.getElementById('contentForm'))