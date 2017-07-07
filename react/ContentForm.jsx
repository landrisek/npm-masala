import React, {Component} from 'react'
import ReactDOM from 'react-dom'

var LINKS = {}
var SIZE = 0

/** @todo https://www.npmjs.com/package/react-tag-input */
export default class ContentForm extends Component {
    constructor(props){
        super(props);
        var name = this.constructor.name;
        this.state = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data'))
        LINKS = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data-links'))
    }
    addInsert(key) {
        return <div key={key}><label>{key}</label><input className='form-control'
                      id={key}
                      onChange={this.update.bind(this)}
                      onKeyPress={this.insert.bind(this)}
                      style={{width: 'auto'}}
                      value={this.state[key]}
        /></div>
    }
    addPlain(key) {
        var width = (this.state[key].length + 5) * 8
        width.toString()
        return <input className='form-control'
                    id={key}
                    key={key}
                    onChange={this.update.bind(this)}
                    onDragOver={this.drop.bind(this)}
                    onDrop={this.drop.bind(this)}
                    style={{fontWeight:'bold',float:'left',width:width}}
                    value={this.state[key]}/>
    }
    addSelect(key) {
        var options = this.state[key]
        var container = []
        for (var value in options) {
            if('type' != value) {
                var id = key + '-' + value
                container.push(<option key={id} value={value}>{options[value]}</option>)
            }
        }
        var id = 'edit-' + key
        container.push(<option key={id} value='edit'>edit...</option>)
        return <select className='form-control'
                       id={key}
                       key={key}
                       onChange={this.edit.bind(this)}
                       style={{float:'left',width: 'auto'}} >
                    {container}
                </select>
    }
    addSubmit(key) {
        return <input
            className='btn btn-success'
            key={key}
            onClick={this.submit.bind(this)}
            type='submit'
            value='Submit' />
    }
    addText(key) {
        return <input className='form-control'
                    id={key}
                    key={key}
                    onChange={this.change.bind(this)}
                    onKeyPress={this.edit.bind(this)}
                    style={{width: 'auto', float:'left'}}
                    value={this.state[key][0]}
                />
    }
    attached() {
        var body = [];
        SIZE = 0
        for (var key in this.state) {
            if(undefined == this.state[key].type && 'select' != key && 'plain' != key) {
                body.push(this.addPlain(key))
            } else if('select' == this.state[key].type) {
                body.push(this.addSelect(key))
            } else if('text' == this.state[key].type) {
                body.push(this.addText(key))
            }
            if('select' !== key && 'plain' !== key) {
                SIZE++
            }
        }
        return body;
    }
    change(event) {
        var element = this.state[event.target.id]
        element[0] = event.target.value
        var state = []
        state[event.target.id] = element
        this.setState(state)
    }
    drop(event) {
        console.log('drag')
    }
    edit(event) {
        var state = []
        if('Enter' == event.key && 0 === event.target.value.length) {
            state = this.state
            delete state[event.target.id]
        } else if('Enter' == event.key) {
            state[event.target.id] = new Object()
            var options = event.target.value.split(',')
            for(var option in options) {
                if(options[option].length > 0) {
                    state[event.target.id][option] = options[option]
                }
            }
            state[event.target.id]['type'] = 'select'
            this.setState(state)
        } else if('edit' === event.target.value) {
            var element = this.state[event.target.id]
            element.type = 'text'
            element[0] = this.getValue(event.target.id)
            state[event.target.id] = element
            this.setState(state)
        }
        this.setState(state)
    }
    getValue(key) {
        var element = ''
        for(var value in this.state[key]) {
            if(this.state[key][value].length > 0 && 'type' != value) {
                element += this.state[key][value] + ','
            }
        }
        return element
    }
    insert(event) {
        var state = []
        var id = SIZE + 1
        state[id] = new Object()
        state[event.target.id] = ''
        if('Enter' != event.key || '' === event.target.value) {

        } else if('select' == event.target.id) {
            state[id].type = event.target.id
            state[id][0] = event.target.value
            this.setState(state)
        } else if('plain' == event.target.id) {
            state[id] = event.target.value
            this.setState(state)
        }
    }
    render() {
        return <div key='content'>
                    <div>{this.attached()}</div>
                    <div style={{clear:'both'}}></div>
                    <form>{this.addInsert('select')}{this.addInsert('plain')}{this.addSubmit('submit')}</form>
                </div>
    }
    submit(event) {
        event.preventDefault()
        var data = new Object()
        data['content'] = JSON.stringify(this.state)
        $.ajax({type: 'post', data: data, url: LINKS['submit'], async: false})
    }
    update(event) {
        var state = []
        state[event.target.id] = event.target.value
        if(0 === event.target.value.length) {
            state = this.state
            delete state[event.target.id]
        }
        this.setState(state)
    }
    write(event) {
        if('Enter' == event.key) {
            var content = event.target.textContent.split('...')
            var state = []
            for(var key in content) {
                if('string' == typeof(this.state[key])) {
                    state[key] = content[key]
                }
            }
            this.setState(state)
        }
    }
}
ReactDOM.render(<ContentForm />, document.getElementById('contentForm'))