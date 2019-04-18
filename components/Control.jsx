import {Autocomplete, Button, Checkbox, Difference, Download, Icon, Info, Label, MultiSelect, Number, Paginator, Password, ProgressBar, RadioList, 
    SelectBox, Sort, Text, TextArea, Warning} from './Components.jsx'
import {Editor} from 'react-draft-wysiwyg'
import {convertToRaw, ContentState, EditorState} from 'draft-js'
import draftToHtml from 'draftjs-to-html'
import Datetime from 'react-datetime'
import {Boolean, DateTime, Email, Equal, Phone, Required, Message, Minimum, Submit} from './Validators.jsx'
import React, {Component} from 'react'
import 'react-draft-wysiwyg/dist/react-draft-wysiwyg.css'
import 'react-datetime/css/react-datetime.css'
import {stateFromHTML} from 'draft-js-import-html';
import {Upload} from './Upload.jsx'

var PAGINATOR = {}

export default class Control extends Component {
    constructor(props) {
        super(props)
        this.state = {_autocomplete:{data:{},position:0},_clicked:{},_paginator:{current:1,last:1,sum:0},_submit:undefined,_order:{},_where:{},_wysiwyg:{}}
    }
    Autocomplete(props, state) {
        return Autocomplete(props,
                            state,
                            this.state._autocomplete, 
                            this.onBlurAutocomplete.bind(this), 
                            this.onChangeAutocomplete.bind(this, props, state),
                            this.onKeyAutocomplete.bind(this, props))
    }
    Boolean(props, state) {
        return Boolean(this.constructor.name, props, state)
    }
    Button(props, state) {
        return this.IsClicked(props.id, Button(props, state, this.onClickButton.bind(this, props)))
    }
    CheckboxFilter(props, state) {
        return Checkbox(props, state, this.onChangeCheckboxFilter.bind(this, props))
    }
    Checkbox(props, state) {
        return Checkbox(props, state, this.onChangeCheckbox.bind(this, props))
    }
    componentDidMount() {
        var regex = new RegExp(this.constructor.name.toLowerCase() + '=(.*)')
        var search = regex.exec(window.location.search)
        if(null != search) {
            var pattern = JSON.parse(search[1].replace(/\&(.*)/, '').split('%22').join('"').split('%20').join(' '))
            this.state._order = pattern._order
            this.state._where = pattern._where
            this.state._paginator.current = parseInt(pattern._page)
        } else if(null != this.props._where) {
            this.state._where = this.props._where
        }
        if(undefined == this.props.if || this.props.if) {
            fetch(this.props.data.state.link,
            {body: JSON.stringify(this.state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
            response => response.json()).then(state => {
                this.setState(state)
                this.paginator()
                this.OnFetch() })
        }

    }
    DateTime(props, state) {
        return <><label style={{marginTop:'10px'}}>{props.label}</label>
                <Datetime id={props.id}
                         locale={'cs'}
                         onChange={this.onChangeDateTime.bind(this, props)}
                         value={state} /></>
    }
    DateTimeFrom(props, state) {
        return <><label style={{marginTop:'10px'}}>{props.label}</label>
                <Datetime id={props.id}
                         locale={'cs'}
                         onChange={this.onChangeDateTimeFrom.bind(this, props)}
                         value={state} /></>
    }
    Difference(origin, state) {
        return Difference(origin, state)
    }
    Download(props, state) {
        return Download(props, state)
    }
    Editor(props, state) {
        var self = this
        if(undefined == this.state._wysiwyg[props.id]) {
            this.state._wysiwyg[props.id] = EditorState.createWithContent(stateFromHTML(state))
        }
        return <><label>{props.label}</label><Editor editorClassName={'form-control'}
                editorState={this.state._wysiwyg[props.id]}
                onEditorStateChange={function (event) { self.onChangeEditor(props, event); } }
                toolbarClassName={'toolbarClassName'}
                wrapperClassName={'wrapperClassName'} /></>
    }
    Email(props, state) {
       return Email(this.constructor.name, props, state)
    }
    Equal(props, state, value) {
        return Equal(this.constructor.name, props, state, value)
    }
    Icon(props) {
        return this.IsClicked(props.id, Icon(props, this.onClickIcon.bind(this, props)))
    }
    IsClicked(props, component) {
        if(this.state._clicked[props]) {
            return <div className={'btn btn-success waiting'} style={{marginTop:'10px'}}>&nbsp;&nbsp;&nbsp;&nbsp;</div>
        }
        return component
    }
    Info(props, state) {
        return Info(props, state, this.onClickInfo.bind(this, props))
    }
    Label(props) {
        return Label(props)
    }
    Message(state) {
        return Message(state)
    }
    MultiSelectFilter(props, state) {
        return MultiSelect(this.state._autocomplete,
                           props,
                           state,
                           this.onBlurAutocomplete.bind(this),
                           this.onChangeMultiSelect.bind(this, props),
                           this.onClickMultiSelectFilter.bind(this), 
                           this.onKeyMultiSelectFilter.bind(this),
                           this.onRemoveMultiSelectFilter.bind(this))
    }
    Minimum(props, state) {
        return Minimum(this.constructor.name, props, state)
    }
    Number(props, state) {
        return Number(props, state, this.onChangeNumber.bind(this, props))
    }
    onBlurAutocomplete() {
        this.setState({_autocomplete:{list:{},position:0}})
    }
    onChangeAutocomplete(props, state) {
        fetch(this.props.data[props.id].link,
            {body: event.target.value, headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
            response => response.json()).then(state => { this.setState(state) })
    }
    onChangeCheckbox(props, event) {
        if(1 == event.target.value) {
            this.setState(this.OnChangeCheckbox(props, 0))
        } else {
            this.setState(this.OnChangeCheckbox(props, 1))
        }
    }
    OnChangeCheckbox(props, state) {
        return {[props.id]:String(state)}
    }
    onChangeCheckboxFilter(props, event) {
        if(1 == event.target.value) {
            this.setState(this.OnChangeCheckboxFilter(props, 0))
        } else {
            this.setState(this.OnChangeCheckboxFilter(props, 1))
        }
    }
    OnChangeCheckboxFilter(props, state) {
        var where = this.state._where
        where[props.id] = String(state)
        return {[props.id]:String(state)}
    }
    onChangeDateTime(props, state) {
        if('object' == typeof state) {
            this.setState(this.OnChangeDateTime(props, state.format('Y-MM-DD HH:mm:ss')))
        } else {
            this.setState(this.OnChangeDateTime(props, state))
        }
    }
    OnChangeDateTime(props, state) {
        return {[props.id]:state}
    }
    onChangeDateTimeFrom(props, state) {
        if('object' == typeof state) {
            this.state._where[props.id + ' >='] = state.format('Y-MM-DD HH:mm:ss')
        } else {
            this.state._where[props.id + ' >='] = state
        }
        this.setState({_where:this.state._where})
    }
    onChangeEditor(props, state) {
        var wysiwyg = this.state._wysiwyg
        wysiwyg[props.id] = state
        var state = this.OnChangeEditor(props, draftToHtml(convertToRaw(state.getCurrentContent())))
        state._wysiwyg = wysiwyg
        this.setState(state)
    }
    OnChangeEditor(props, state) {
        return {[props.id]:state.substr(3, state.length - 9)}
    }
    onChangeMultiSelect(props, event) {
        this.state._autocomplete = {id:props.id,position:0,value:event.target.value}
        this.setState({_autocomplete:this.state._autocomplete})
    }
    onChangeNumber(props, event) {
        this.setState(this.OnChangeNumber(props, event.target.value))
    }
    OnChangeNumber(props, state) {
        return {[props.id]:state}
    }
    onChangeSelectBoxFilter(props, event) {
        this.state._where[props.id] = event.target.value
        this.setState({_where:this.state._where})
    }
    onChangeSelectBox(props, event) {
        this.setState(this.OnChangeSelectBox(props, event.target.value))
    }
    OnChangeSelectBox(props, state) {
        return {[props.id]:state}
    }
    onChangeText(props, event) {
        this.setState(this.OnChangeText(props, event.target.value))
    }
    OnChangeText(props, state) {
        return {[props.id]:state}
    }
    onClickButton(props, state) {
        var self = this
        this.state._clicked[props.id] = true
        fetch(props.link,
             {body: JSON.stringify(this.state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
              response => response.json()).then(state => { self.OnClickButton(props, state) })
    }
    OnClickButton(props, state) {
        if(parseInt(state._paginator.last) >= parseInt(state._paginator.current)) {
            state[props.id] = state._paginator.current / (state._paginator.last / 100)
            this.setState(state)
            this.reload()
            this.onClickButton(props)
        } else {
            state._paginator.current = 1
            state[props.id] = 0
            delete state._clicked[props.id]
            this.setState(state)
            this.reload()
        }
    }
    onClickIcon(props) {
        this.state._clicked[props.id] = true
        this.setState({_clicked:this.state._clicked})
        fetch(props.link,
             {body: JSON.stringify(this.state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
              response => response.json()).then(state => { delete state._clicked[props.id] 
                                                           this.setState(state) })
    }
    onClickInfo(props) {
        this.setState({[props.id]:false})
    }
    onClickMultiSelectFilter(event) {
        var props = JSON.parse(event.target.getAttribute('data-props'))
        if(undefined == this.state._where[props.id]) {
            this.state._where[props.id] = []
        }
        this.state._where[props.id].push(parseInt(props.value))
        this.setState({_where:this.state._where})
    }
    onClickPaginator(event) {
        event.preventDefault()
        this.state._clicked._paginator = true
        this.setState({_clicked:this.state._clicked})
        this.state._paginator.current = parseInt(event.target.getAttribute('data-page'))
        fetch(this.props.data.state.link,
             {body: JSON.stringify(this.state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
                response => response.json()).then(state => { delete state._clicked._paginator, this.setState(state); this.reload() })
    }
    onClickRadio(props, event) {
        this.setState({[props.id]:event.target.value})
    }
    onClickSort(props) {
        var sort = this.state._order[props.id]
        if(undefined == sort) {
            this.state._order[props.id] = '-desc'
        } else if('-desc' == sort) {
            this.state._order[props.id] = '-asc'
        } else {
            delete this.state._order[props.id]
        }
        fetch(this.props.data.state.link,
            {body: JSON.stringify(this.state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
               response => response.json()).then(state => { this.setState(state) })
    }
    onClickSubmit(props) {
        this.state._clicked[props.id] = true
        this.setState({_clicked:this.state._clicked})
        var state = this.state
        var wysiwyg = this.state._wysiwyg
        fetch(this.props.data[props.id].link,
            {body: JSON.stringify(state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
               response => response.json()).then(state => { delete state._clicked[props.id]
                                                            state._wysiwyg = wysiwyg
                                                            this.setState(state)
                                                            this.paginator()
                                                            this.reload()
                                                            this.OnClickSubmit(props) })
    }
    OnClickSubmit(props) { }
    onClickUpload(props, state) {
        var files = this.state[props.id]
        delete files[state]
        this.setState({[props.id]:files})
    }
    onDropUpload(props, files, rejected) {
        var self = this
        for(var key in files) {
            var file = files[key]
            delete files[key]
            break
        }
        if(file.type.match('image')) {
            var reader = new FileReader()
            reader.onload = function() {
                fetch(props.link, {body: reader.result, method: 'POST'}).then(response => response.json()).then(state => {
                    state[props.id].push(files[file].name)
                    self.setState(state)
                })
            }
            reader.readAsDataURL(files[file])
        } else {
            fetch(props.link, {body: file, method: 'POST'}).then(response => response.json()).then(state => {
                if(undefined == self.state[props.id]) {
                    self.setState({[props.id]:{[state.file]:file.name}})
                } else {
                    self.state[props.id][state.file] = file.name
                    this.setState({[props.id]:self.state[props.id]})
                }
                if(Object.keys(files).length > 0) {
                    self.onDropUpload(props, files, rejected)
                }
            })
        }
    }
    OnFetch() { }
    onFilter(props, event) {
        this.state._where[props.id] = event.target.value
        this.setState({_where: Object.assign({}, this.state._where)})
    }
    onKeyAutocomplete(props, event) {
        var state = this.state._autocomplete
        if(state.position == (parseInt(event.target.getAttribute('length')) - 1)) {
            state.position = 0
        } else if(13 == event.keyCode) {
            this.setState({[props.id]:state.data[event.target.getAttribute('current')].replace(/<\/?[^>]+(>|$)/g, '')})
            state.data = {}; state.position = 0
        } else if(38 == event.keyCode && 0 == state[props.id].position) {
            state.position = state.data.length
        } else if(38 == event.keyCode) {
            state.position--
        } else if(40 == event.keyCode) {
            state.position++
        }
        this.setState({_autocomplete:state})
    }
    onKeyMultiSelectFilter(event) {
        var props = JSON.parse(event.target.getAttribute('data-props'))
        if(13 == event.keyCode && undefined == this.state._where[props.id]) {
            this.state._where[props.id] = [props.value]
            this.setState({_autocomplete:{list:{},value:''},_where:this.state._where})
        } else if(13 == event.keyCode) {
            this.state._where[props.id].push(props.value)
            this.setState({_autocomplete:{},_where:this.state._where})
        } else if(40 == event.keyCode || 38 == event.keyCode) {
            var autocomplete = this.state._autocomplete
            if(40 == event.keyCode && props.sum > this.state._autocomplete.position) {
                autocomplete.position++
            } else if(40 == event.keyCode && this.state._autocomplete.position >= props.sum) {
                autocomplete.position = 0
            } else if(38 == event.keyCode && 0 == this.state._autocomplete.position) {
                autocomplete.position = props.sum
            } else if(38 == event.keyCode && this.state._autocomplete.position > 0) {
                autocomplete.position--
            }
            this.setState({_autocomplete:autocomplete})
        }
    }
    onKeyTextFilter(props) {
        if(13 == event.keyCode) {
            this.onClickSubmit({id:'state'})
        }
    }
    onRemoveMultiSelectFilter(event) {
        var props = JSON.parse(event.target.getAttribute('data-props'))
        var where = []
        for(var value in this.state._where[props.id]) {
            if(this.state._where[props.id][value] != props.value) {
                where.push(this.state._where[props.id][value])
            }
        }
        this.state._where[props.id] = where
        this.setState({_where:this.state._where})
    }
    paginator() {
        if(PAGINATOR[this.constructor.name]) {
            fetch(this.props.data._paginator.link,
                  {body: JSON.stringify(this.state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
                  response => response.json()).then(paginator => { this.setState({_paginator:paginator}) })
        }
    }
    Paginator(props, state) {
        PAGINATOR[this.constructor.name] = true
        return this.IsClicked('_paginator', Paginator(props, state, this.onClickPaginator.bind(this)))
    }
    Password(props, state) {
        return Password(props, state, this.onChangeText.bind(this, props))
    }
    Phone(props, state) {
        return Phone(this.constructor.name, props, state)
    }
    ProgressBar(props, state) {
        return ProgressBar(props, state)
    }
    RadioList(props, state) {
        return RadioList(props, state, this.onClickRadio.bind(this, props))
    }
    reload() {
        var hash = window.location.href.replace(/(.*)\#/, '')
        var url = window.location.href.replace(/\?(.*)|\#(.*)/, '') + '?'
        var data = JSON.stringify({_order:this.state._order,_page:this.state._paginator.current,_where:this.state._where})
        url += this.constructor.name.toLowerCase() + '=' + data
        if(hash) {
            url += '#' + hash
        }
        window.history.pushState('', 'title', url)
    }
    Required(props, state) {
        return Required(this.constructor.name, props, state)
    }
    Row(key, state) {
        var row = []
        for(var column in state) {
            row.push(<td key={'grid-col-' + column}>{state[column]}</td>)
        }
        return <tr key={'row-' + key}>{row}</tr>
    }
    Rows(state) {
        var rows = []
        for(var key in state) {
            rows.push(this.Row(key, state[key]))
        }
        return rows
    }
    SelectBox(props, state) {
        return SelectBox(props, state, this.onChangeSelectBox.bind(this, props))
    }
    SelectBoxFilter(props, state) {
        return SelectBox(props, state, this.onChangeSelectBoxFilter.bind(this, props))
    }
    Sort(props, state) {
        return Sort(props, state, this.onClickSort.bind(this, props))
    }
    Submit(props) {
        return this.IsClicked(props.id, Submit(this.constructor.name, props, this.onClickSubmit.bind(this, props)))
    }
    Text(props, state) {
        return Text(props, state, this.onChangeText.bind(this, props))
    }
    TextArea(props, state) {
        return TextArea(props, state, this.onChangeText.bind(this, props))
    }
    TextFilter(props, state) {
        return Text(props, state, this.onFilter.bind(this, props), this.onKeyTextFilter.bind(this, props))
    }
    Upload(props, state) {
        var files = []
        if(state) {
            for(var file in state) {
                files.push(<li className={'list-group-item'} key={props.id + file}>{state[file]}
                        <button aria-label={'Close'} className={'close'} onClick={this.onClickUpload.bind(this, props, file)} type={'button'}><span aria-hidden={'true'}>&times;</span></button>
                </li>)
            }
        }
        return <div key={props.id}>
                    <center><Upload data={props} multiple={true} onDrop={this.onDropUpload.bind(this, props)} /></center>
                    <ul className={'list-group'}>{files}</ul>
                </div>
    }
    validate(validators) {
        this.setState(validators)
    }
    Warning(state) {
        return Warning(state)
    }
}