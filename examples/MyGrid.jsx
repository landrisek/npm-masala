import Control from '../components/Control.jsx'
import React from 'react'

export default class MyGrid extends Control {
    constructor(props){
        super(props)
    }
    render() {
        return <div>
            {this.MultiSelectFilter(this.props.data.producers_id, this.state._where.producers_id)}
            {this.DateTimeFrom(this.props.data.updated, this.state._where.updated)}
            {this.DateTimeFrom(this.props.data.created, this.state._where.created)}
            {this.Submit(this.props.data.state, this.state.state)}<br/>
            <div className={'clear'}></div>
            {this.Paginator(this.props.data._paginator, this.state._paginator)}
            <table className={'table table-striped table-hover'} style={{position:'relative'}}>
                <thead id={'header'}>
                    <tr className={'grid-labels'}>
                        {this.Sort(this.props.data.id, this.state._order.id)}
                        {this.Sort(this.props.data.name, this.state._order.name)}
                        {this.Sort(this.props.data.perex, this.state._order.perex)}
                        {this.Sort(this.props.data.text, this.state._order.text)}
                    </tr>
                    <tr className={'grid-columns'}>
                        <th key={'id'}></th>
                        <th key={'name'}>{this.TextFilter({id:'name'}, this.state._where.name)}</th>
                        <th key={'perex'}>{this.TextFilter({id:'perex'}, this.state._where.perex)}</th>
                        <th key={'text'}>{this.TextFilter({id:'text'}, this.state._where.text)}</th>
                        <th key={'submit'}></th>
                    </tr>
                </thead>
                <tbody>{this.Rows(this.state.rows)}</tbody>
            </table>
            {this.Paginator(this.props.data._paginator, this.state._paginator)}
        </div>
    }
    Row(key, state) {
        return <tr key={'row-' + key}>
            <td key={'grid-col-id'}>{state.id}</td>
            <td key={'grid-col-name'}>{state.name}</td>
            <td key={'grid-col-perex'} title={state.perex}>{state.perex ? state.perex.slice(0, 30) + '...' : ''}</td>
            <td key={'grid-col-text'} title={state.text}>{state.text ? state.text.slice(0, 30) + '...' : ''}</td>
            <td key={'grid-col-edit'}>{this.Icon({id:key})}</td>
        </tr>
    }
}
