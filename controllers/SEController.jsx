import React from 'react'

let INVALID = {}

export default class SEController extends React.Component {
    constructor(props) {
        super(props)
        this.state = {Autocomplete:{Data:{},Position:0},Clicked:{},Crops:{},Group:null,Image:undefined,Menu:window.location.hash.replace(/#/, ''),Order:{},Paginator:{Current:1,Last:1,Sum:0},Submit:undefined,Where:{},Wysiwyg:{}}
    }
    componentDidMount() {
        console.log('open your EventSource here.');
    }
    Click(props) {
        if(this.state.Clicked[props.id]) {
            this.state.Clicked[props.id]++;
            this.setState({Clicked:this.state.Clicked});
            let self = this;
            setTimeout(function(){ self.Click(props) }, 3000);
        }
    }
    invalidate(props, state) {
        for(let key in props.data) {
            if(undefined == INVALID[props.data[key]] && state) {
                INVALID[props.data[key]] = {[props.id]:true}
            } else if(state) {
                INVALID[props.data[key]][props.id] = true;
            } else if(false == state && INVALID[props.data[key]] && INVALID[props.data[key]][props.id]) {
                delete INVALID[props.data[key]][props.id];
            }
        }
        return state
    }
    IsClicked(props, component) {
        if(this.state.Clicked[props.id]) {
            return <div className={props.className ? props.className :  'btn btn-success'} style={{marginTop:'10px'}}>{this.state.Clicked[props.id]}</div>
        }
        return component
    }
    isValid(key) {
        return undefined == INVALID[key] || 0 == Object.entries(INVALID[key]).length
    }
    page(key) {
        let state = this.constructor.name == key ? this.state : this.state[key]
        fetch(this.props.data.page.replace(/\?key\=.*/, '') + '?key=' + key,
            {body: JSON.stringify(state),
                headers: {Accept: 'application/json','Access-Control-Request-Headers': 'content-type','Content-Type': 'application/json'}, method: 'POST'}).then(
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