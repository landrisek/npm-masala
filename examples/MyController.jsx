import {Container} from './node_modules/masala/components/Container.jsx'
import {Controller} from './node_modules/masala/controllers/Controller.jsx'
import {Menu} from './node_modules/masala/controllers/Controller.jsx'
import React from 'react'
import ReactDOM from 'react-dom'

export default class MyController extends Container.Inject(React.Component, Controller, Menu) {
    constructor(props) {
        super(props);
        this.state.menu = window.location.hash.replace(/#/, '')
    }

    render() {
        return (<div>
            {this.Menu(this.props.data.menu, this.state.menu)}
            <div className={'cleaner'}></div>
            <div style={{display: this.state.menu1 ? 'block' : 'none'}}>{'MyComponent'}</div>
            <div style={{display: this.state.menu2 ? 'block' : 'none'}}>{'MyOtherComponent'}</div>
        </div>)
    }
}

var id = 'MyPresenter';
var controller = document.getElementById(id);
ReactDOM.render(<MyPresenter data={JSON.parse(controller.getAttribute('props'))}/>, controller);
