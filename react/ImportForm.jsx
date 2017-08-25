import axios from 'axios'
import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import Form from './Form.jsx'

var LINKS = {}

export default class ImportForm extends Form {
    constructor(props){
        super(props)
        var name = this.constructor.name;
        LINKS = JSON.parse(document.getElementById(name[0].toLowerCase() + name.substring(1, name.length)).getAttribute('data-links'));
    }
    done(payload) {
        super.done(payload)
        var element = this.state['done']
        element.Attributes.style = {display:'block'}
        this.setState({'done':element})
    }
    prepare(event) {
        var data = this.state[event.target.id].Attributes.data
        if(undefined != data.header && 'string' == typeof(data.header)) {
            document.getElementById('masala-message-modal-body').insertAdjacentHTML('afterbegin', '<p>' + data.header + '</p>')
            $('#trigger-message').trigger('click')
            return
        }
        this.run(data, event.target.id + '-progress')
    }
    submit() {
        if(true == this.validate()) {
            var save = this.state['save']
            var file = this.state['file']
            var data = new Object()
            for (var upload in this.state['file'].Attributes.value) break;
            data.file = upload
            save.Attributes.style = {display:'none'}
            file.Attributes.style = {display:'none'}
            this.setState({'save':save,'file':file})
            var prepare = this.state['prepare']
            prepare.Attributes.class = 'btn btn-success disabled'
            prepare.Attributes.style = {display:'block'}
            axios.post(LINKS['import'], data).then(response => {
                prepare.Attributes.data = response.data
                prepare.Attributes.class = 'btn btn-success'
                this.setState({'prepare':prepare})
            })
            this.setState({'prepare':prepare})
        }
    }
    render() {
        return <div>{this.attached()}</div>
    }
}
var element = document.getElementById('importForm')
if(null != element) {
    ReactDOM.render(<ImportForm />, document.getElementById('importForm'))
}
