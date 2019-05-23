import Presenter from './node_modules/masala/controllers/Presenter.jsx'
import React from 'react'
import ReactDOM from 'react-dom'
import MyComponent from './MyComponent.jsx'

export default class MyPresenter extends Presenter {
    constructor(props) {
        super(props)
        this.state = {menu1:false,menu2:false}
        this.state[window.location.hash.replace(/#/, '')] = true
    }
    header() {
        if(this.props.data.permission) {
            return <div className={'navbar navbar-default'}>
                <div className={'container'}>
                    <div className={'navbar-collapse collapse'}>
                        <ul className={'nav navbar-nav'}>
                            <li className={this.state.menu1 ? 'active' : ''}>
                                <a id={'menu1'} onClick={this.show.bind(this)}>{this.props.data.menu1}</a>
                            </li>
                            <li></li>
                            <li className={this.state.menu2 ? 'active' : ''}>
                                <a id={'menu2'} onClick={this.show.bind(this)}>{this.props.data.menu2}</a>
                            </li>
                            <li></li>
                        </ul>
                    </div>
                </div>
            </div>
        }
    }
    render() {
        return (<div>
            {this.header()}
            <div className={'cleaner'}></div>
                <div style={{display:this.state.menu1 ? 'block' : 'none'}}><MyComponent data={this.Build(this.props.data.myData)} /></div>
                <div style={{display:this.state.menu2 ? 'block' : 'none'}}><MyComponent data={this.Build(this.props.data.myOtherData)} /></div>
            </div>)
    }
    show(event) {
        event.preventDefault()
        var state = {menu1:false,menu2:false}
        state[event.target.id] = true
        window.history.pushState('', 'title', window.location.href.replace(/#.*/, '') + '#' + event.target.id)
        this.setState(state)
    }
}

var id = 'MyPresenter'
var presenter = document.getElementById(id)
ReactDOM.render(<MyPresenter data={JSON.parse(presenter.getAttribute('props'))} />, presenter)