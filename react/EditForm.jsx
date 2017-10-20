import Form from './Form.jsx'
import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import request from 'sync-request'

var LINKS = {}

export default class EditForm extends Form {
    constructor(props){
        super(props)
        var name = this.constructor.name;
        LINKS = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data-links'));
    }
    attached() {
        var body = [];
        for (var key in this.state) {
            var closure = this[this.state[key].Method]
            if('function' == typeof(closure)) {
                if('addHidden' == this.state[key].Method) {
                } else if('addSubmit' == this.state[key].Method || 'addCheckbox' == this.state[key].Method || 'addMessage' == this.state[key].Method) {
                    body.push(<div className='row' key={key}>
                            <div className='form-group col-md-6'>
                                <div className='form-group input-group'>
                                    {this[this.state[key].Method](key)}
                                </div>
                            </div>
                        </div>
                    )
                } else {
                    body.push(<div className='row' key={key}>
                            <div className='form-group col-md-6'>
                                <div className='form-group input-group'>
                                    <span className='input-group-addon'>{this.state[key].Label}</span>
                                    {this[this.state[key].Method](key)}
                                </div>
                            </div>
                        </div>
                    )
                }
            }
        }
        return body;
    }
    done(payload) {
        super.done(payload)
        var element = this.state['done']
        element.Attributes.style = {display:'block'}
        this.setState({'done':element})
    }
    isUnique(value, key, form) {
        if(undefined == value || '' == value || undefined == form.primary) {
            console.log('It is possible that primary data is missing, cannot apply unique validator.');
            return false
        }
        var unique = new Object()
        unique[key] = value
        return JSON.parse(request('POST', LINKS['unique'], {json:{primary:form.primary.Attributes.value,value:unique}}).getBody('utf8'))
    }
    onChange(event) {
        var element = this.state[event.target.id]
        if('checkbox' == event.target.type && 1 == event.target.value) {
            element.Attributes.value = 0
            element.Attributes.checked = null
        } else if('checkbox' == event.target.type) {
            element.Attributes.value = 1
            element.Attributes.checked = 'checked'
        } else {
            element.Attributes.value = event.target.value
        }
        var state = []
        state[event.target.id]
        var edit = this.state['edit']
        edit.Attributes.style = {display:'none'}
        state['edit'] = edit
        this.setState(state)
    }
    submit() {
        if(true == this.validate()) {
            var data = new Object()
            for(var key in this.state) {
                data[key] = this.state[key].Attributes.value
            }
            var element = this.state['edit'];
            element.Attributes.style = {display:'block'}
            var response = JSON.parse(request('POST', LINKS['submit'], { json: data }).getBody('utf8'))
            if(undefined != response.message) {
                element.Label = response.message
            }
            if(undefined != response.remove && false != response.remove) {
                document.getElementById(response.remove).remove()
            }
            this.setState({ 'edit': element })
        }
    }
    render() {
        return <div>{this.attached()}</div>
    }
}
ReactDOM.render(<EditForm />, document.getElementById('editForm'))