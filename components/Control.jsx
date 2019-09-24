import React from 'react'

let INVALID = {};

export default class Control extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            Autocomplete: {data: {}, position: 0},
            Clicked: {},
            Crops: {},
            Group: null,
            Image: undefined,
            Paginator: {Current: 1, Last: 1, Sum: 0},
            Submit: undefined,
            Order: {},
            Where: {},
            Wysiwyg: {}
        }
    }

    buildUrl() {
        let hash = window.location.href.replace(/(.*)\#/, '');
        let url = window.location.href.replace(/\?(.*)|\#(.*)/, '') + '?';
        let data = JSON.stringify({
            Order: this.state.Order,
            Page: this.state.Paginator.Current,
            Where: this.state.Where
        });
        url += this.constructor.name.toLowerCase() + '=' + data;
        if (hash) {
            url += '#' + hash
        }
        window.history.pushState('', 'title', url)
    }

    componentDidMount() {
        let regex = new RegExp(this.constructor.name.toLowerCase() + '=(.*)');
        let search = regex.exec(window.location.search);
        if (null != search) {
            let pattern = JSON.parse(decodeURI(search[1]));
            this.state.Order = pattern.Order;
            this.state.Where = pattern.Where;
            this.state.Paginator.Current = parseInt(pattern.Page)
        } else if (null != this.props.Where) {
            this.state.Where = this.props.Where
        }
        for (let key in this.state.Paginator) {
            this.state.Paginator[key] = this.state.Paginator[key].toString()
        }
        if (this.ComponentDidMount()) {
            fetch(this.props.data.state.link,
                {
                    body: JSON.stringify(this.state),
                    headers: {Accept: 'application/json', 'Content-Type': 'application/json'},
                    method: 'POST'
                }).then(
                response => response.json()).then(state => {
                this.setState(state);
                this.page();
            })
        }
    }

    ComponentDidMount() {
        return undefined == this.props.if || this.props.if
    }

    invalidate(props, state) {
        if (state && Object.entries(INVALID).length > 0) {
            return INVALID[props.id] = true
        } else if (state) {
            INVALID = {[props.id]: true};
            return true
        } else if (false == state && Object.entries(INVALID).length > 0 && undefined != INVALID[props.id]) {
            delete INVALID[props.id]
        }
    }

    IsClicked(props, component) {
        if (this.state.Clicked[props]) {
            return <div className={'btn btn-success waiting'} style={{marginTop: '10px'}}>&nbsp;&nbsp;&nbsp;&nbsp;</div>
        }
        return component
    }

    isValid() {
        return 0 == Object.entries(INVALID).length
    }

    page() {
        fetch(this.props.data.page,
            {
                body: JSON.stringify(this.state),
                headers: {Accept: 'application/json', 'Content-Type': 'application/json'},
                method: 'POST'
            }).then(
            response => response.json()).then(state => {
            this.setState(state)
        })
    }
}
