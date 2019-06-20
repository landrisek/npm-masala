import React from 'react'

export class MultiSelectFilter extends React.Component {
    MultiSelectFilter(props, data) {
        let values = {}
        let state = data ? data : []
        for (var value in state) {
            values[state[value]] = true
        }
        let container = []
        let options = []
        props.sum = 0
        if (this.state.Autocomplete.id == props.id) {
            options.push(<li className={'list-group-item'}
                             onClick={this.onBlurMultiSelect.bind(this)}
                             key={props.id + '-cancel'}
                             style={props.sum == this.state.Autocomplete.position ? {
                                 backgroundColor: 'rgb(51, 122, 183)',
                                 color: 'white'
                             } : {}}
                             value={value}>{props.cancel}</li>)
            props.sum++
        }
        for (let value in props.data) {
            let key = value.replace('_', '')
            if (undefined != values[key]) {
                container.push(<li className={'list-group-item'}
                                   data-props={JSON.stringify({id: props.id, value: key})}
                                   onClick={this.onRemoveMultiSelectFilter.bind(this)}
                                   key={props.id + '-' + value}>
                    {props.data[value]}
                    <span className={'glyphicon glyphicon-remove'}
                          data-props={JSON.stringify({id: props.id, value: key})} style={{float: 'right'}}></span>
                </li>)
            } else if (this.state.Autocomplete.id == props.id && null != props.data[value].toLowerCase().match(this.state.Autocomplete.value.toLowerCase())) {
                if (props.sum == this.state.Autocomplete.position) {
                    var selected = value.replace('_', '')
                }
                options.push(<li className={'list-group-item'}
                                 data-props={JSON.stringify({id: props.id, value: key})}
                                 onClick={this.onClickMultiSelectFilter.bind(this)}
                                 key={props.id + '-' + value}
                                 style={props.sum == this.state.Autocomplete.position ? {
                                     backgroundColor: 'rgb(51, 122, 183)',
                                     color: 'white'
                                 } : {}}>{props.data[value]}</li>)
                props.sum++
            }
        }
        return <div key={'elements-' + props.id}><label>{props.label}</label>
            {container.length ? <ul className={'list-group'}>{container}</ul> : ''}
            <div className={'input-group'}>
                <input className={'form-control'}
                       data-props={JSON.stringify({id: props.id, state: state, sum: props.sum, value: selected})}
                       key={props.id + '-autocomplete'}
                       onClick={this.onChangeMultiSelect.bind(this, props)}
                       onChange={this.onChangeMultiSelect.bind(this, props)}
                       onKeyDown={this.onKeyMultiSelectFilter.bind(this)}
                       placeholder={props.placeholder}
                       type={'text'}
                       value={this.state.Autocomplete.value && this.state.Autocomplete.id == props.id ? this.state.Autocomplete.value : ''}/>
                {options.length ? <div className={'input-group-btn'}>
                    <ul className={'dropdown-menu dropdown-menu-right list-group'}
                        size={100}
                        style={{
                            display: 'block',
                            left: '-100px',
                            position: 'absolute',
                            zIndex: 99,
                            maxHeight: '300px',
                            overflowY: 'scroll'
                        }}>
                        {options}
                    </ul>
                </div> : ''}
            </div>
        </div>
    }
    onBlurMultiSelect() {
        this.setState({Autocomplete:{list:{},position:0}})
    }
    onChangeMultiSelect(props, event) {
        this.state.Autocomplete = {id:props.id,position:0,value:event.target.value}
        this.setState({Autocomplete:this.state.Autocomplete})
    }
    onClickMultiSelectFilter(event) {
        let props = JSON.parse(event.target.getAttribute('data-props'))
        if(undefined == this.state.Where[props.id]) {
            this.state.Where[props.id] = []
        }
        this.state.Where[props.id].push(props.value)
        this.setState({Where:this.state.Where})
    }
    onKeyMultiSelectFilter(event) {
        let props = JSON.parse(event.target.getAttribute('data-props'))
        if(13 == event.keyCode && undefined == this.state.Where[props.id]) {
            this.state.Where[props.id] = [props.value]
            this.setState({Autocomplete:{list:{},value:''},Where:this.state.Where})
        } else if(13 == event.keyCode) {
            this.state.Where[props.id].push(props.value)
            this.setState({Autocomplete:{},Where:this.state.Where})
        } else if(40 == event.keyCode || 38 == event.keyCode) {
            let autocomplete = this.state.Autocomplete
            if(40 == event.keyCode && props.sum > this.state.Autocomplete.position) {
                autocomplete.position++
            } else if(40 == event.keyCode && this.state.Autocomplete.position >= props.sum) {
                autocomplete.position = 0
            } else if(38 == event.keyCode && 0 == this.state.Autocomplete.position) {
                autocomplete.position = props.sum
            } else if(38 == event.keyCode && this.state.Autocomplete.position > 0) {
                autocomplete.position--
            }
            this.setState({Autocomplete:autocomplete})
        }
    }
    onRemoveMultiSelectFilter(event) {
        let props = JSON.parse(event.target.getAttribute('data-props'))
        let where = []
        for(let value in this.state.Where[props.id]) {
            if(this.state.Where[props.id][value] != props.value) {
                where.push(this.state.Where[props.id][value])
            }
        }
        this.state.Where[props.id] = where
        this.setState({Where:this.state.Where})
    }
}