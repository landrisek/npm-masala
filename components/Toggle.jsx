import React from 'react'

export class Toggle extends React.Component {
    onClickToggle(props, state) {
        if(state) {
            this.setState({[props.id]:this.state[props.id] = false})
        } else {
            this.setState({[props.id]:this.state[props.id] = true})
        }
        let self = this
        fetch(props.link,
            {body: JSON.stringify(this.state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
            response => response.json()).then(state => { self.OnClickToggle(props, state) })
    }
    OnClickToggle(props, state) { }
    Toggle(props, state) {
        return <><label className={'switch'} style={{display:'inline-block',height:'34px',position:'relative',width:'60px'}}>
            <input style={{height:0,opacity:0,width:0}} onClick={this.onClickToggle.bind(this, props, state)} type={'checkbox'} />
            <span className={'slider round'}
                  style={{backgroundColor:state ? '#2196F3' : '#ccc',borderRadius:'34px',bottom:0,cursor:'pointer',left:0,position:'absolute',right:0,top:0,transition:'.4s',WebkitTransition:'.4s'}}></span>
        </label>{state ? props.on : props.off}</>
    }
}