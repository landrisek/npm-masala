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
        this.run(JSON.parse(this.state[event.target.id].Attributes.data), event.target.id + '-progress')
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
            var self = this
            $.ajax({type: 'post',url: LINKS['import'],data: data, success: function(payload) {
                prepare.Attributes.data = JSON.stringify(payload)
                prepare.Attributes.class = 'btn btn-success'
                self.setState({'prepare':prepare})
            }})
            this.setState({'prepare':prepare})
        }
    }
    render() {
        return <div>{this.attached()}</div>
    }
}
ReactDOM.render(<ImportForm />, document.getElementById('importForm'))