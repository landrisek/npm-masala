import React from 'react'

let INVALID = {}

export default class Controller extends React.Component {
    constructor(props) {
        super(props)
    }
    componentDidMount() {
        let regex = new RegExp(this.constructor.name.toLowerCase() + '=(.*)')
        let search = regex.exec(window.location.search)
        if(null != search) {
            let pattern = JSON.parse(decodeURI(search[1]))
            this.state.Order = pattern.Order
            this.state.Where = pattern.Where
            this.state.Paginator.Current = parseInt(pattern.Page)
        } else if(null != this.props.Where) {
            this.state.Where = this.props.Where
        }
        for(let key in this.state.Paginator) {
            this.state.Paginator[key] = this.state.Paginator[key].toString()
        }
        if(undefined == this.props.data.componentDidMount) {
            fetch(this.props.data.submit.link,
                {body: JSON.stringify(this.state),
                    headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
                response => response.json()).then(state => { this.setState(state); this.page(this.constructor.name); })
        }
        for(let key in this.props.data.componentDidMount) {
            fetch(this.props.data.componentDidMount[key],
                {body: JSON.stringify(this.state[key]),
                headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
                response => response.json()).then(state => { this.setState({[key]:state}); this.page(key); })
        }
    }
    getState() {
        return {Autocomplete:{data:{},position:0},Clicked:{},Crops:{},Group:null,Image:undefined,Menu:window.location.hash.replace(/#/, ''),Paginator:{Current:1,Last:1,Sum:0},Submit:undefined,Order:{},Where:{},Wysiwyg:{}}
    }
    invalidate(props, state) {
        for(let key in props.data) {
            if(undefined == INVALID[props.data[key]] && state) {
                INVALID[props.data[key]] = {[props.id]:true}
            } else if(state) {
                INVALID[props.data[key]][props.id] = true
            } else if(false == state && INVALID[props.data[key]] && INVALID[props.data[key]][props.id]) {
                delete INVALID[props.data[key]][props.id]
            }
        }
        return state
    }
    IsClicked(props, component) {
        if(this.state.Clicked[props]) {
            return <div className={'btn btn-success waiting'} style={{marginTop:'10px'}}>&nbsp;&nbsp;&nbsp;&nbsp;</div>
        }
        return component
    }
    isValid(key) {
        return undefined == INVALID[key] || 0 == Object.entries(INVALID[key]).length
    }
    page(key) {
        let state = this.constructor.name == key ? this.state : this.state[key]
        fetch(this.props.data.page.replace(/\?key\=.*/, '') + '?key=' + key,
            {body: JSON.stringify(state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
            response => response.json()).then(state => { this.constructor.name == key ? this.setState(state) : this.setState({[key]:state}) })
    }
    reload() {
        let hash = window.location.href.match('#') ? window.location.href.replace(/(.*)\#/, '') : ''
        let url = window.location.href.replace(/\?(.*)|\#(.*)/, '') + '?'
        let data = JSON.stringify({Order:this.state.Order,Page:this.state.Paginator.Current,Where:this.state.Where})
        url += this.constructor.name.toLowerCase() + '=' + data
        if(hash) {
            url += '#' + hash
        }
        window.history.pushState('', 'title', url)
    }
}