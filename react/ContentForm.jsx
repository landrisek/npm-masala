import React, {Component} from 'react'
import ReactDOM from 'react-dom'

var CURRENT = 'current'
var CONTENT = 'content'
var LABELS = 'labels'
var LINKS = {}
var PLAIN = 'plain'
var SELECT = 'select'
var STATISTICS = 'statistics'
var SOURCE = 'source'
var WRITE = 'write'

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
                      value={this.state[key]}
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
                      onChange={this.change.bind(this)}
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
            if('object' == typeof(this.state[CONTENT][key])) {
                body.push(this.addSelect(key))
            } else if('string' == typeof(this.state[CONTENT][key])) {
                body.push(this.addText(key))
            }
        }
        return body;
    }
    change(event) {
        var state = []
        state[CONTENT] = this.state[CONTENT]
        state[CONTENT][event.target.id] = event.target.value
        this.setState(state)
    }
    click(event) {
        this.state[CURRENT] = parseInt(event.target.id) + 1
    }
    drop(event) {
        console.log('drag')
    }
    edit(event) {
        var state = []
        state[CONTENT] = this.state[CONTENT]
        if('Enter' == event.key && /;/.exec(event.target.value)) {
            state[CONTENT][event.target.id] = this.explode(state[CONTENT][event.target.id])
        } else if ('Enter' == event.key && '' == event.target.value) {
            delete state[CONTENT][event.target.id]
        } else if ('Enter' == event.key) {
            state[CONTENT][event.target.id] = state[CONTENT][event.target.id]
        } else if ('edit' == event.target.value) {
            state[CONTENT][event.target.id] = this.implode(state[CONTENT][event.target.id])
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
        for(var id in this.state[CONTENT]) {
            var key = parseInt(id)
            if(key >= this.state[CURRENT]) {
                state[CONTENT][key + 1] = this.state[CONTENT][key]
            } else {
                state[CONTENT][key] = this.state[CONTENT][key]
            }
        }
        if('select' == event.target.id) {
            state[CONTENT][this.state[CURRENT]] = event.target.value.split(';')
            state[SELECT] = ''
        } else if('plain' == event.target.id) {
            state[CONTENT][this.state[CURRENT]] = event.target.value
            state[PLAIN] = ''
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
    reset() {
        var i=0;
        var data = new Object()
        for(var key in this.state[CONTENT]) {
            data[i++] = this.state[CONTENT][key]
        }
        return data
    }
    submit(event) {
        event.preventDefault()
        var data = new Object
        data.content = JSON.stringify(this.reset())
        $.ajax({type: 'post', data: data, url: LINKS['submit'], async: false})
    }
    update(event) {
        var state = []
        if('select' == event.target.id) {
            state[SELECT] = event.target.value
        } else {
            state[PLAIN] = event.target.value
        }
        this.setState(state)
    }
    write(event) {
        var contents = this.reset()
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