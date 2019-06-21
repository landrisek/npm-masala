import React from 'react'

export class Toggle extends React.Component {
    onClickToggle(props, state) {
        if(state) {
            this.setState({[props.id]:false})
        } else {
            this.setState({[props.id]:true})
        }
        return
        let self = this
        this.setState({Clicked:{[props.id]:true}})
        fetch(props.link,
            {body: JSON.stringify(this.state), headers: {Accept: 'application/json','Content-Type': 'application/json'}, method: 'POST'}).then(
            response => response.json()).then(state => { self.OnClickButton(props, state) })
    }
    OnClickToggle(props, state) {
        if(parseInt(state.Paginator.Last) >= parseInt(state.Paginator.Current)) {
            state[props.id] = state.Paginator.Current / (state.Paginator.Last / 100)
            this.setState(state)
            this.reload()
            this.onClickButton(props)
        } else {
            state.Paginator.Current = 1
            state[props.id] = 0
            delete state.Clicked[props.id]
            this.setState(state)
            this.reload()
        }
    }
    Toggle(props, state) {
        return <><label className={'switch'} style={{display:'inline-block',height:'34px',position:'relative',width:'60px'}}>
            <input style={{height:0,opacity:0,width:0}} onClick={this.onClickToggle.bind(this, props, state)} type={'checkbox'} />
            <span className={'slider round'}
                  style={{backgroundColor:state ? '#2196F3' : '#ccc',borderRadius:'34px',bottom:0,cursor:'pointer',left:0,position:'absolute',right:0,top:0,transition:'.4s',WebkitTransition:'.4s'}}></span>
        </label></>
    }
}