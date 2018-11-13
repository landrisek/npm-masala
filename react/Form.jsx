import axios from 'axios'
import Datetime from 'react-datetime'
import Dropzone from 'react-dropzone'
import React, {Component} from 'react'
import ReactCrop from 'react-image-crop';
import request from 'sync-request'

var LINKS = {}
var ROW = 'row'
var VALIDATORS = 'validators'

export default class Form extends Component {
    constructor(props, id) {
        super(props);
        this.state = JSON.parse(document.getElementById(id).getAttribute('data'))
        LINKS = JSON.parse(document.getElementById(id).getAttribute('data-links'))
    }
    attached() {
        var body = [];
        for (var key in this.state[ROW]) {
            var closure = this[this.state[ROW][key].Method]
            if('function' == typeof(closure)) {
                body.push(this[this.state[ROW][key].Method](key))
            }
        }
        return body;
    }
    addAction(key) {
        return <a key={key}
                  href={this.state[ROW][key].Attributes.href}
                  style={this.state[ROW][key].Attributes.style}
                  className={this.state[ROW][key].Attributes.className}
                  onClick={this.bind(this.state[ROW][key].Attributes.onClick)}>{this.state[ROW][key].Label}</a>
    }
    addButton(key) {
        console.log('Add button method is suppose to be overloaded by children component.')
    }
    addCheckbox(key) {
        return <div key={key} style={this.state[ROW][key].Attributes.style}>
                    <input checked={this.state[ROW][key].Attributes.checked}
                           id={key}
                           onChange={this.change.bind(this)}
                           type='checkbox'
                           value={this.state[ROW][key].Attributes.value}  />
                    <label style={{marginLeft:'10px'}}>{this.state[ROW][key].Label}</label>
        </div>
    }
    addDateTime(key) {
        return <Datetime locale={this.state[ROW][key].Attributes.locale}
                         onChange={this.datetime.bind(this, key)}
                         value={this.state[ROW][key].Attributes.value}
            />
    }
    addHidden(key) {
        return <input key={key} type='hidden' />
    }
    addMessage(key) {
        return <div key={key} 
                    className='alert alert-success'
                    role='alert'
                    style={this.state[ROW][key].Attributes.style}>
                    {this.state[ROW][key].Label}</div>
    }
    addMultiSelect(key) {
        return <div key={key}><label>{this.state[ROW][key].Label}</label>
            <select className={this.state[ROW][key].Attributes.className}
                       id={this.state[ROW][key].Attributes.id}
                       multiple
                       style={this.state[ROW][key].Attributes.style}
                       onChange={this.change.bind(this)}>{this.getOptions(key)}>
        </select></div>
    }
    addProgressBar(key) {
        return <div key={key}
            style={this.state[ROW][key].Attributes.style}
            className='progress'><div
            className='progress-bar'
            style={{width:this.state[ROW][key].Attributes.width+'%'}}></div></div>
    }
    addRadioList(key) {
        var container = [];
        var options = this.state[ROW][key].Attributes.data;
        container.push(<div>{this.addValidator(key)}</div>);
        for (var value in options) {
            container.push(<div key={value}><input name={key} 
                                    onClick={this.bind(this.state[ROW][key].Attributes.onClick)}
                                    type='radio'
                                    value={value} />
                                    <label>{this.state[ROW][key].Attributes.data[value]}</label></div>);
        }
        return container;
    }
    addSelect(key) {
        return <div key={key}>
                <label>{this.state[ROW][key].Label}</label>
                <select className={this.state[ROW][key].Attributes.className}
                                      defaultValue={this.state[ROW][key].Attributes.value}
                                      id={key}
                                      style={this.state[ROW][key].Attributes.style}
                                      onChange={this.change.bind(this)}>{this.getOptions(key)}
                </select>
                {this.addValidator(key)}
        </div>
    }
    addSubmit(key) {
        return <input
            className={this.state[ROW][key].Attributes.className}
            data={this.state[ROW][key].Attributes.data}
            id={key}
            key={key}
            onClick={this.bind(this.state[ROW][key].Attributes.onClick)}
            style={this.state[ROW][key].Attributes.style}
            type='submit'
            value={this.state[ROW][key].Label} />
    }
    addUpload(key) {
        var files = []
        for(var file in this.state[ROW][key].Attributes.value) {
            var id = key + file
            files.push(<li key={id} className='list-group-item'>{this.state[ROW][key].Attributes.value[file]}</li>)
        }
        return <div key={key} style={this.state[ROW][key].Attributes.style}>
                <img src={this.state[ROW][key].Attributes.content} alt={this.state[ROW][key].Attributes.alt} />
                <Dropzone multiple={false}
                          onDrop={this.onDrop.bind(this, key)}
                          style={{height:'200px',borderWidth:'2px',borderColor:'rgb(102, 102, 102)',borderStyle:'dashed',borderRadius:'5px'}}>
                    <center>{this.state[ROW][key].Label}</center>
                </Dropzone>
                <ul className='list-group'>{files}</ul>
                {this.addValidator(key)}
            </div>
    }
    addValidator(key) {
        if(undefined != this.state[VALIDATORS][key]) {
            return <div key={'validator-' + key} className='bg-danger'>{this.state[VALIDATORS][key]}</div>
        }
    }
    addText(key) {
        return <div key={key} className='input-group'>
            <label>{this.state[ROW][key].Label}</label>
            <input 
            id={key}
            className={this.state[ROW][key].Attributes.className}
            data={this.state[ROW][key].Attributes.data}
            onBlur={this.bind(this.state[ROW][key].Attributes.onBlur)}
            onClick={this.bind(this.state[ROW][key].Attributes.onClick)} 
            onChange={this.change.bind(this)}
            readOnly={this.state[ROW][key].Attributes.readonly}
            style={this.state[ROW][key].Attributes.style}
            type={this.state[ROW][key].Attributes.type}
            value={this.state[ROW][key].Attributes.value} />
            <div>{this.addValidator(key)}</div></div>
    }
    addTextArea(key) {
        return <div key={key} className='input-group'>
            <textarea
                id={key}
                className={this.state[ROW][key].Attributes.className}
                data={this.state[ROW][key].Attributes.data}
                onBlur={this.bind(this.state[ROW][key].Attributes.onBlur)}
                onClick={this.bind(this.state[ROW][key].Attributes.onClick)}
                onChange={this.change.bind(this)}
                style={this.state[ROW][key].Attributes.style}>
                {this.state[ROW][key].Attributes.value}</textarea>
            <div>{this.addValidator(key)}</div></div>
    }
    addTitle(key) {
        return <h1 key={key} className={this.state[ROW][key].Attributes.className}>{this.state[ROW][key].Attributes.value}</h1>
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
        if('checkbox' == event.target.type && 1 == event.target.value) {
            var element = this.state[ROW][event.target.id]
            element.Attributes.value = 0
            element.Attributes.checked = null
        } else if('checkbox' == event.target.type) {
            var element = this.state[ROW][event.target.id]
            element.Attributes.value = 1
            element.Attributes.checked = 'checked'
        } else {
            var element = this.state[ROW][event.target.id]
            element.Attributes.value = event.target.value
        }
        var state = []
        state[event.target.id] = element
        this.setState(state)
    }
    datetime(key, event) {
        var state = []
        state[key] = this.state[ROW][key]
        state[key].Attributes.value = event.format(this.state[ROW][key].Attributes.format.toUpperCase())
        this.setState(state)
    }
    done(payload) {
        var response = JSON.parse(request('POST', LINKS['done'], { json: payload }).getBody('utf8'))
        var state = []
        for (var key in this.state[ROW]) {
            var element = this.state[ROW][key]
            element.Attributes.style = {display:'none'}
            state[key] = element
        }
        this.setState(state)
        return response
    }
    getOptions(key) {
        var container = []
        var options = this.state[ROW][key].Attributes.data
        for (var value in options) {
            if(this.state[ROW][key].Attributes.value == value) {
                container.push(<option selected key={value} value={value}>{this.state[ROW][key].Attributes.data[value]}</option>)
            } else {
                container.push(<option key={value} value={value}>{this.state[ROW][key].Attributes.data[value]}</option>)
            }
        }
        return container
    }
    load(key, file, name) {
        var state = []
        state[ROW] = this.state[ROW]
        var self = this
        if(undefined == state[ROW][key].Attributes.value) {
            state[ROW][key].Attributes.value = []
        }
        var data = new Object()
        for(var row in state[ROW]) {
            data[key] = state[ROW][row]
        }
        data._file = file
        data._name = name
        data._key = key
        axios.post(LINKS.save, data).then(response => {
            state[ROW][key].Attributes.value[response.data] = name
            state[ROW]._submit.Attributes.className = 'btn btn-success'
            self.setState(state)
        })
        state[ROW]._submit
        state[ROW]._submit.Attributes.className = 'btn btn-success disabled'
        this.setState(state)
    }
    onDrop(key, files) {
        var element = this.state[ROW][key]
        for(var file in files) {
            this.save(key, files[file])
        }
        var state = []
        state[key] = element
        this.setState(state)
    }
    save(key, file) {
        var self = this
        if(null == file.type.match('image')) {
            axios.get(file.preview).then(response => { self.load(key, response.data, file.name); })
        } else {
            var reader = new FileReader()
            reader.onload = function() {
                self.load(key, reader.result, file.name)
            }
            reader.readAsDataURL(file)
        }
    }
    prepare(event) {
        var response = JSON.parse(request('POST', LINKS.prepare, { json: this.state }).getBody('utf8'))
        this.run(response, event.target.id + '-progress')
    }
    submit() {
        var data = this.validate()
        if(null != data) {
            request('POST', LINKS.submit, { json: {Row:data} })
        }
    }
    run(payload, progress) {
        if(parseInt(payload.Stop) > parseInt(payload.Offset)) {
            axios.post(LINKS.run, payload).then(response => {  this.run(response.data, progress) })
            var element = this.state[ROW][progress]
            element.Attributes.width = payload.Offset / (payload.Stop / 100)
            var state = []
            state[ROW] = this.state[ROW]
            state[ROW][progress] = element
            this.setState(state)
        } else {
            this.done(payload)
        }
    }
    validate() {
        var data = new Object()
        for(var key in this.state[ROW]) {
            data[key] = this.state[ROW][key].Attributes.value
        }
        var state = []
        state[VALIDATORS] = JSON.parse(request('POST', LINKS.validate, {json: {row:data}}).getBody('utf8'))
        for (var validator in state[VALIDATORS]) {
            this.setState(state)
            return null
        }
        return data
    }
}