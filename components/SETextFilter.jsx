import React from 'react'

export class SETextFilter extends React.Component {
    onChangeSETextFilter(props, event) {
        this.state.Where[props.id] = event.target.value;
        this.setState({Where: Object.assign({}, this.state.Where)})
    }

    onKeySETextFilter(event) {
        console.log(event);
        if (13 == event.keyCode) {
            this.onClickSEButton({id: 'state'})
        }
    }

    SETextFilter(props, state) {
        return <div className={'form-group'}>
            <label htmlFor={props.label}>{props.label}</label>
            <input className={'form-control'}
                   onChange={this.onChangeSETextFilter.bind(this, props)}
                   onKeyDown={this.onKeySETextFilter.bind(this)}
                   value={state}
                   type={'text'}/>
        </div>
    }
}
