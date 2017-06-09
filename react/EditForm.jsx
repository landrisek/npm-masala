import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import Form from './Form.jsx'

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
                } else if('addSubmit' == this.state[key].Method || 'addCheckbox' == this.state[key].Method) {
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
    submit() {
        if(true == this.validate()) {
            var data = new Object()
            for(var key in this.state) {
                data[key] = this.state[key].Attributes.value
            }
            $.ajax({type:'post', url:LINKS['submit'],data:data,async:false})
        }
    }
    render() {
        return <div>{this.attached()}</div>
    }
}
ReactDOM.render(<EditForm />, document.getElementById('editForm'))