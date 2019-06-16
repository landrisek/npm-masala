import React from 'react'

export class TextFilter extends React.Component {
    onChangeTextFilter(props, event) {
        this.state.Where[props.id] = event.target.value
        this.setState({Where: Object.assign({}, this.state.Where)})
    }
    onKeyTextFilter(event) {
        if(13 == event.keyCode) {
            this.onClickSubmit({id:'state'})
        }
    }
    TextFilter(props, state) {
        return <div className={'form-group'}>
            <label htmlFor={props.label}>{props.label}</label>
            <input className={'form-control'}
                   onChange={this.onChangeTextFilter.bind(this, props)}
                   onKeyDown={this.onKeyTextFilter.bind(this)}
                   value={state}
                   type={'text'} />
        </div>
    }
}