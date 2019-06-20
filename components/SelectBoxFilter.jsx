import React from 'react'

export class SelectBoxFilter {
    onChangeSelectBoxFilter(props, event) {
        this.state.Where[props.id] = event.target.value
        this.setState({Where:this.state.Where})
    }
    SelectBoxFilter(props, state) {
        let options = []
        options.push(props.placeholder ? <option key={props.id + '-prompt'}>{props.placeholder}</option> : '')
        for(let key in props.data) {
            if(props.data[key] == state || key == state) {
                options.push(<option selected key={key} value={key}>{props.data[key]}</option>)
            } else {
                options.push(<option key={key} value={key}>{props.data[key]}</option>)
            }
        }
        return <div className={'form-group'}><label>{props.label}</label><select className={'form-control'} key={props.id} onChange={this.onChangeSelectBoxFilter.bind(this, props)}>{options}</select></div>
    }
}