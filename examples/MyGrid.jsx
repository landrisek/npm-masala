import {Container} from '../components/Container.jsx'
import {Control} from '../components/Control.jsx'
import {DateTimeFrom} from '../components/DateTimeFrom.jsx'
import {MultiSelectFilter} from '../components/MultiSelectFilter.jsx'
import {Paginator} from '../components/Paginator.jsx'
import {React} from 'react'
import {Sort} from '../components/Sort.jsx'
import {Submit} from '../components/Submit.jsx'
import {TextFilter} from "../components/TextFilter";

export default class MyGrid extends Container.Inject(React.Component, Control, DateTimeFrom, MultiSelectFilter, Paginator, Sort, Submit, TextFilter) {
    constructor(props){
        super(props)
    }
    render() {
        return <div>
            {this.MultiSelectFilter(this.props.data.producers_id, this.state.Where.producers_id)}
            {this.DateTimeFrom(this.props.data.updated, this.state.Where.updated)}
            {this.DateTimeFrom(this.props.data.created, this.state.Where.created)}
            {this.Submit(this.props.data.state, this.state.state)}<br/>
            <div className={'clear'}></div>
            {this.Paginator(this.props.data.Paginator, this.state.Paginator)}
            <table className={'table table-striped table-hover'} style={{position:'relative'}}>
                <thead id={'header'}>
                    <tr className={'grid-labels'}>
                        {this.Sort(this.props.data.id, this.state.Order.id)}
                        {this.Sort(this.props.data.name, this.state.Order.name)}
                        {this.Sort(this.props.data.perex, this.state.Order.perex)}
                        {this.Sort(this.props.data.text, this.state.Order.text)}
                    </tr>
                    <tr className={'grid-columns'}>
                        <th key={'id'}></th>
                        <th key={'name'}>{this.TextFilter({id:'name'}, this.state.Where.name)}</th>
                        <th key={'perex'}>{this.TextFilter({id:'perex'}, this.state.Where.perex)}</th>
                        <th key={'text'}>{this.TextFilter({id:'text'}, this.state.Where.text)}</th>
                        <th key={'submit'}></th>
                    </tr>
                </thead>
                <tbody>{this.Rows(this.state.Rows)}</tbody>
            </table>
            {this.Paginator(this.props.data.Paginator, this.state.Paginator)}
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
